<?php
abstract class DevblocksApplication {
	
}

/**
 * The superclass of instanced extensions.
 *
 * @abstract 
 * @ingroup plugin
 */
class DevblocksExtension {
	public $manifest = null;
	public $id  = '';
	
	/**
	 * Constructor
	 *
	 * @private
	 * @param DevblocksExtensionManifest $manifest
	 * @return DevblocksExtension
	 */
	
	function __construct($manifest=null) {
        if(empty($manifest))	
        	return;
        
		$this->manifest = $manifest;
		$this->id = $manifest->id;
	}
	
	function getParams() {
		return $this->manifest->getParams();
	}
	
	function setParam($key, $value) {
		return $this->manifest->setParam($key, $value);
	}
	
	function getParam($key,$default=null) {
		return $this->manifest->getParam($key, $default);
	}
};

abstract class Extension_DevblocksContext extends DevblocksExtension {
	/**
	 * @param unknown_type $as_instances
	 * @param unknown_type $with_options
	 * @return Extension_DevblocksContext[]
	 */
	public static function getAll($as_instances=false, $with_options=null) {
		$contexts = DevblocksPlatform::getExtensions('devblocks.context', $as_instances);
		
		if($as_instances)
			DevblocksPlatform::sortObjects($contexts, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($contexts, 'name');

		if(!empty($with_options)) {
			if(!is_array($with_options))
				$with_options = array($with_options);
			
			foreach($contexts as $k => $context) {
				@$options = $context->params['options'][0];
				
				if(!is_array($options) || empty($options)) {
					unset($contexts[$k]);
					continue;
				}
				
				if(count(array_intersect(array_keys($options), $with_options)) != count($with_options))
					unset($contexts[$k]);
			}
		}
		
		return $contexts;
	}
	
	/**
	 * Lazy loader + cache
	 * @param unknown_type $context
	 * @return Extension_DevblocksContext
	 */
	public static function get($context) {
		static $contexts = null;
		
		/*
		 * Lazy load
		 */

		if(isset($contexts[$context]))
			return $contexts[$context];
		
		if(!isset($contexts[$context])) {
			if(null == ($ext = DevblocksPlatform::getExtension($context, true)))
				return;
			
			$contexts[$context] = $ext;
			return $ext;
		}
	}
	
   	function authorize($context_id, Model_Worker $worker) {
		return true;
	}
    
	abstract function getRandom();
    abstract function getMeta($context_id);
    abstract function getContext($object, &$token_labels, &$token_values, $prefix=null);
    abstract function getChooserView();
    function getViewClass() {
    	return @$this->manifest->params['view_class'];
    }
    abstract function getView($context=null, $context_id=null, $options=array());
    function lazyLoadContextValues($token, $dictionary) { return array(); }
    
    protected function _lazyLoadCustomFields($context, $context_id) {
		$fields = DAO_CustomField::getByContext($context);
		$token_values['custom'] = array();

		$field_values = array_shift(DAO_CustomFieldValue::getValuesByContextIds($context, $context_id));
		
		foreach(array_keys($fields) as $cf_id) {
			$token_values['custom'][$cf_id] = '';
			$token_values['custom_' . $cf_id] = '';
			
			if(isset($field_values[$cf_id])) {
				// The literal value
				$token_values['custom'][$cf_id] = $field_values[$cf_id];
				
				// Stringify
				if(is_array($field_values[$cf_id])) {
					$token_values['custom_'.$cf_id] = implode(', ', $field_values[$cf_id]);
				} elseif(is_string($field_values[$cf_id])) {
					$token_values['custom_'.$cf_id] = $field_values[$cf_id];
				}
			}
		}
		
		return $token_values;
    } 
};

abstract class Extension_DevblocksEvent extends DevblocksExtension {
	const POINT = 'devblocks.event'; 
	
	private $_labels = array();
	private $_values = array();
	
	public static function getAll($as_instances=false) {
		$events = DevblocksPlatform::getExtensions('devblocks.event', $as_instances);
		if($as_instances)
			DevblocksPlatform::sortObjects($events, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($events, 'name');
		return $events;
	}
	
	public static function getByContext($context, $as_instances=false) {
		$events = self::getAll(false);
		
		foreach($events as $event_id => $event) {
			if(isset($event->params['contexts'][0])) {
				$contexts = $event->params['contexts'][0]; // keys
				if(!isset($contexts[$context]))
					unset($events[$event_id]);
			}
		}
		
		if($as_instances) {
			foreach($events as $event_id => $event)
				$events[$event_id] = $event->createInstance();
		}
			
		return $events;
	}
	
	protected function _importLabelsTypesAsConditions($labels, $types) {
		$conditions = array();
		
		foreach($types as $token => $type) {
			if(!isset($labels[$token]))
				continue;
			
			$label = $labels[$token];
			
			// Strip any modifiers
			if(false !== ($pos = strpos($token,'|')))
				$token = substr($token,0,$pos);
				
			$conditions[$token] = array('label' => $label, 'type' => $type);
		}
		
		foreach($labels as $token => $label) {
			if(preg_match("#.*?_{0,1}custom_(\d+)#", $token, $matches)) {
				
				if(null == ($cfield = DAO_CustomField::get($matches[1])))
					continue;
					
				$conditions[$token] = array('label' => $label, 'type' => $cfield->type);
				
				switch($cfield->type) {
					case Model_CustomField::TYPE_DROPDOWN:
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						$conditions[$token]['options'] = $cfield->options;
						break;
				}
			}
		}
		
		return $conditions;
	}
	
	abstract function setEvent(Model_DevblocksEvent $event_model=null);
	
	function setLabels($labels) {
		asort($labels);
		$this->_labels = $labels;
	}
	
	function setValues($values) {
		$this->_values = $values;
	}
	
	function getLabels($trigger = null) {
		// Lazy load
		if(empty($this->_labels))
			$this->setEvent(null);
			
		if(null != $trigger && !empty($trigger->variables)) {
			foreach($trigger->variables as $k => $var) {
				$this->_labels[$k] = '(variable) ' . $var['label'];
			}
		}
		
		return $this->_labels;
	}
	
	function getValues() {
		return $this->_values;
	}
	
	function getValuesContexts($trigger) {
		$contexts_to_macros = DevblocksEventHelper::getContextToMacroMap();
		$macros_to_contexts = array_flip($contexts_to_macros);

		$cfields = array();
		$custom_fields = DAO_CustomField::getAll();
		$vars = array();
		
		// cfields
		$labels = $this->getLabels($trigger);
		if(is_array($labels))
		foreach($labels as $token => $label) {
			if(preg_match("#.*?_{0,1}custom_(\d+)#", $token, $matches)) {
				@$cfield_id = $matches[1];
				
				if(empty($cfield_id))
					continue;
				
				if(!isset($custom_fields[$cfield_id]))
					continue;
				
				switch($custom_fields[$cfield_id]->type) {
					case Model_CustomField::TYPE_WORKER:
						$cfields[$token] = array(
							'label' => $label,
							'context' => CerberusContexts::CONTEXT_WORKER,
						);
						break;
					default:
						continue;
						break;
				}
			}
		}
		
		// behavior vars
		$vars = DevblocksEventHelper::getVarValueToContextMap($trigger);
		
		return array_merge($cfields, $vars);		
	}
	
	// [TODO] Cache results for this request
	function getConditions($trigger) {
		$conditions = array(
			'_month_of_year' => array('label' => 'Month of year', 'type' => ''),
			'_day_of_week' => array('label' => 'Day of week', 'type' => ''),
			'_time_of_day' => array('label' => 'Time of day', 'type' => ''),
		);
		$custom = $this->getConditionExtensions();
		
		if(!empty($custom) && is_array($custom))
			$conditions = array_merge($conditions, $custom);
		
		// Trigger variables
		if(is_array($trigger->variables))
		foreach($trigger->variables as $key => $var) {
			$conditions[$key] = array('label' => '(variable) ' . $var['label'], 'type' => $var['type']);
		}
		
		// Plugins
		// [TODO] Work in progress
		// [TODO] This should filter by event type
		$manifests = Extension_DevblocksEventCondition::getAll(false);
		foreach($manifests as $manifest) {
			$conditions[$manifest->id] = array('label' => $manifest->params['label']);
		}
		
		DevblocksPlatform::sortObjects($conditions, '[label]');
			
		return $conditions;
	}
	
	abstract function getConditionExtensions();
	abstract function renderConditionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runConditionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict);
	
	function renderCondition($token, $trigger, $params=array(), $seq=null) {
		$conditions = $this->getConditions($trigger);
		$condition_extensions = $this->getConditionExtensions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case '_month_of_year':
				return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_month_of_year.tpl');
				break;

			case '_day_of_week':
				return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_day_of_week.tpl');
				break;

			case '_time_of_day':
				return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_time_of_day.tpl');
				break;
			
			default:
				if(null != (@$condition = $conditions[$token])) {
					// Automatic types
					switch($condition['type']) {
						case Model_CustomField::TYPE_CHECKBOX:
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_bool.tpl');
							break;
						case Model_CustomField::TYPE_DATE:
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_date.tpl');
							break;
						case Model_CustomField::TYPE_MULTI_LINE:
						case Model_CustomField::TYPE_SINGLE_LINE:
						case Model_CustomField::TYPE_URL:
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_string.tpl');
							break;
						case Model_CustomField::TYPE_NUMBER:
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
							break;
						case Model_CustomField::TYPE_DROPDOWN:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$tpl->assign('condition', $condition);
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_dropdown.tpl');
							break;
						case Model_CustomField::TYPE_WORKER:
							$tpl->assign('workers', DAO_Worker::getAll());
							return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_worker.tpl');
							break;
						default:
							if(substr($condition['type'],0,4) == 'ctx_') {
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_number.tpl');
							
							} else {
								// Custom
								if(isset($condition_extensions[$token])) {
									return $this->renderConditionExtension($token, $trigger, $params, $seq);
								
								} else {
									// Plugins
									if(null != ($ext = DevblocksPlatform::getExtension($token, true))
										&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */ 
										return $ext->render($this, $trigger, $params, $seq);
									}
								}
							}
							
							break;
					}
				}
				break;
		}
	}
	
	function runCondition($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$logger = DevblocksPlatform::getConsoleLog('Attendant');
		$conditions = $this->getConditions($trigger);
		$extensions = $this->getConditionExtensions();
		$not = false;
		$pass = true;
		
		$now = time();
		
		// Overload the current time? (simulate)
		if(isset($dict->_current_time)) {
			$now = $dict->_current_time;
		}
		
		// Built-in actions
		switch($token) {
			case '_month_of_year':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$months = DevblocksPlatform::importVar($params['months'],'array',array());
				
				switch($oper) {
					case 'is':
						$month = date('n', $now);
						$pass = in_array($month, $months);
						break;
				}
				break;
			case '_day_of_week':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$days = DevblocksPlatform::importVar($params['day'],'array',array());
				
				switch($oper) {
					case 'is':
						$today = date('N', $now);
						$pass = in_array($today, $days);
						break;
				}
				break;
			case '_time_of_day':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$from = DevblocksPlatform::importVar($params['from'],'string','now');
				@$to = DevblocksPlatform::importVar($params['to'],'string','now');
				
				switch($oper) {
					case 'between':
						@$from = strtotime($from, $now);
						@$to = strtotime($to, $now);
						if($to < $from)
							$to += 86400; // +1 day
						$pass = ($now >= $from && $now <= $to) ? true : false;
						break;
				}
				break;
		
			default:
				// Operators
				if(null != (@$condition = $conditions[$token])) {
					if(null == (@$value = $dict->$token)) {
						$value = '';
					}
					
					// Automatic types
					switch($condition['type']) {
						case Model_CustomField::TYPE_CHECKBOX:
							$bool = intval($params['bool']);
							$pass = !empty($value) == $bool;
							break;
							
						case Model_CustomField::TYPE_DATE:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							switch($oper) {
								case 'is':
								case 'between':
									$from = strtotime($params['from']);
									$to = strtotime($params['to']);
									if($to < $from)
										$to += 86400; // +1 day
									$pass = ($value >= $from && $value <= $to) ? true : false;
									break;
							}
							break;
							
						case Model_CustomField::TYPE_MULTI_LINE:
						case Model_CustomField::TYPE_SINGLE_LINE:
						case Model_CustomField::TYPE_URL:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							switch($oper) {
								case 'is':
									$pass = (0==strcasecmp($value,$params['value']));
									break;
								case 'like':
									$regexp = DevblocksPlatform::strToRegExp($params['value']);
									$pass = @preg_match($regexp, $value);
									break;
								case 'contains':
									$pass = (false !== stripos($value, $params['value'])) ? true : false;
									break;
								case 'regexp':
									$pass = @preg_match($params['value'], $value);
									break;
								//case 'words_all':
								//	break;
								//case 'words_any':
								//	break;
							}
							
							// Handle operator negation
							break;
							
						case Model_CustomField::TYPE_NUMBER:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							switch($oper) {
								case 'is':
									$pass = intval($value)==intval($params['value']);
									break;
								case 'gt':
									$pass = intval($value) > intval($params['value']);
									break;
								case 'lt':
									$pass = intval($value) < intval($params['value']);
									break;
							}
							break;
							
						case Model_CustomField::TYPE_DROPDOWN:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							if(!isset($params['values']) || !is_array($params['values'])) {
								$pass = false;
								break;
							}
							
							switch($oper) {
								case 'in':
									$pass = false;
									if(in_array($value, $params['values'])) {
										$pass = true;
									}
									break;
							}
							break;
							
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							if(preg_match("#(.*?_custom)_(\d+)#", $token, $matches) && 3 == count($matches)) {
								@$value = $values[$matches[1]][$matches[2]]; 
							}
							
							if(!is_array($value) || !isset($params['values']) || !is_array($params['values'])) {
								$pass = false;
								break;
							}
							
							switch($oper) {
								case 'is':
									$pass = true;
									foreach($params['values'] as $v) {
										if(!isset($value[$v])) {
											$pass = false;
											break;
										}
									}
									break;
								case 'in':
									$pass = false;
									foreach($params['values'] as $v) {
										if(isset($value[$v])) {
											$pass = true;
											break;
										}
									}
									break;
							}
							break;
							
						case Model_CustomField::TYPE_WORKER:
							@$worker_ids = $params['worker_id'];
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							if(!is_array($value))
								$value = empty($value) ? array() : array($value);
							
							if(is_null($worker_ids))
								$worker_ids = array();
							
							if(empty($worker_ids) && empty($value)) {
								$pass = true;
								break;
							}
							
							switch($oper) {
								case 'in':
									$pass = false;
									foreach($worker_ids as $v) {
										if(in_array($v, $value)) {
											$pass = true;
											break;
										}
									}
									break;
							}
							break;

						default:
							if(substr($condition['type'],0,4) == 'ctx_') {
								$count = (isset($dict->$token) && is_array($dict->$token)) ? count($dict->$token) : 0;
								
								$not = (substr($params['oper'],0,1) == '!');
								$oper = ltrim($params['oper'],'!');
								switch($oper) {
									case 'is':
										$pass = $count==intval($params['value']);
										break;
									case 'gt':
										$pass = $count > intval($params['value']);
										break;
									case 'lt':
										$pass = $count < intval($params['value']);
										break;
								}
							
							} else {
								if(isset($extensions[$token])) {
									$pass = $this->runConditionExtension($token, $trigger, $params, $dict);
								} else {
									if(null != ($ext = DevblocksPlatform::getExtension($token, true))
										&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */ 
										$pass = $ext->run($token, $trigger, $params, $dict);
									}
								}
							}	
							break;
					}
			}
			break;			
		}
		
		// Inverse operator?
		if($not)
			$pass = !$pass;
			
		$logger->info(sprintf("Checking condition '%s'... %s", $token, ($pass ? 'PASS' : 'FAIL')));
		
		return $pass;
	}
	
	// [TODO] Cache results for this request
	function getActions($trigger) {
		$actions = array();
		$custom = $this->getActionExtensions();
		
		if(!empty($custom) && is_array($custom))
			$actions = array_merge($actions, $custom);
		
		// Trigger variables
		if(is_array($trigger->variables))
		foreach($trigger->variables as $key => $var) {
			$actions[$key] = array('label' => 'Set (variable) ' . $var['label']);
		}
		
		// Add plugin extensions
		// [TODO] This should be filtered by event type?
		$manifests = Extension_DevblocksEventAction::getAll(false);
		foreach($manifests as $manifest) {
			$actions[$manifest->id] = array('label' => $manifest->params['label']);
		}
		
		DevblocksPlatform::sortObjects($actions, '[label]');
		
		return $actions;
	}
	
	abstract function getActionExtensions();
	abstract function renderActionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict);
	protected function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {}
	
	function renderAction($token, $trigger, $params=array(), $seq=null) {
		$actions = $this->getActionExtensions();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('trigger', $trigger);
		$tpl->assign('params', $params);

		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);
		
		// Is this an event-provided action?
		if(null != (@$action = $actions[$token])) {
			$this->renderActionExtension($token, $trigger, $params, $seq);
			
		// Nope, it's a global action
		} else {
			switch($token) {
				default:
					// Variables
					if(substr($token,0,4) == 'var_') {
						@$var = $trigger->variables[$token];
						
						switch(@$var['type']) {
							case Model_CustomField::TYPE_CHECKBOX:
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
								break;
							case Model_CustomField::TYPE_DATE:
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
								break;
							case Model_CustomField::TYPE_NUMBER:
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
								break;
							case Model_CustomField::TYPE_SINGLE_LINE:
								return DevblocksEventHelper::renderActionSetVariableString($this->getLabels());
								break;
							case Model_CustomField::TYPE_WORKER:
								return DevblocksEventHelper::renderActionSetVariableWorker();
								break;
							default:
								if(substr(@$var['type'],0,4) == 'ctx_') {
									@$list_context = substr($var['type'],4);
									if(!empty($list_context))
										return DevblocksEventHelper::renderActionSetListVariable($token, $trigger, $params, $list_context);
								}
								return;
								break;
						}
					} else {
						// Plugins
						if(null != ($ext = DevblocksPlatform::getExtension($token, true))
							&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */ 
							$ext->render($this, $trigger, $params, $seq);
						}
					}
					break;
			}
		}		
	}
	
	// Are we doing a dry run?
	function simulateAction($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$actions = $this->getActionExtensions();

		if(null != (@$action = $actions[$token])) {
			if(method_exists($this, 'simulateActionExtension'))
				return $this->simulateActionExtension($token, $trigger, $params, $dict);
			
		} else {
			switch($token) {
				default:
					// Variables
					if(substr($token,0,4) == 'var_') {
						return DevblocksEventHelper::runActionSetVariable($token, $trigger, $params, $dict);
					
					} else {
						// Plugins
						if(null != ($ext = DevblocksPlatform::getExtension($token, true))
							&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */ 
							//return $ext->simulate($token, $trigger, $params, $dict);
						}
					}
					break;
			}
		}		
	}
	
	function runAction($token, $trigger, $params, DevblocksDictionaryDelegate $dict, $dry_run=false) {
		$actions = $this->getActionExtensions();
		
		$out = '';
		
		if(null != (@$action = $actions[$token])) {
			// Is this a dry run?  If so, don't actually change anything
			if($dry_run) {
				$out = $this->simulateAction($token, $trigger, $params, $dict);
			} else {
				$this->runActionExtension($token, $trigger, $params, $dict);
			}
			
		} else {
			switch($token) {
				default:
					// Variables
					if(substr($token,0,4) == 'var_') {
						// Always set the action vars, even in simulation.
						DevblocksEventHelper::runActionSetVariable($token, $trigger, $params, $dict);
						
						if($dry_run) {
							$out = DevblocksEventHelper::simulateActionSetVariable($token, $trigger, $params, $dict);
						} else {
							return;
						}
					
					} else {
						// Plugins
						if(null != ($ext = DevblocksPlatform::getExtension($token, true))
							&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */
							if($dry_run) {
								if(method_exists($ext, 'simulate'))
									$out = $ext->simulate($token, $trigger, $params, $dict);
							} else {
								return $ext->run($token, $trigger, $params, $dict);
							}
						}
					}
					break;
			}
		}
		
		// Append to simulator output
		if(!empty($out)) {
			/* @var $trigger Model_TriggerEvent */
			$all_actions = $this->getActions($trigger);
			$log = EventListener_Triggers::getNodeLog();
			$nodes = $trigger->getNodes();
			
			if(!isset($dict->_simulator_output) || !is_array($dict->_simulator_output))
				$dict->_simulator_output = array();
			
			$output = array(
				'action' => $nodes[array_pop($log)]->title,
				'title' => $all_actions[$token]['label'],
				'content' => $out,
			);
			
			$previous_output = $dict->_simulator_output;
			$previous_output[] = $output;
			$dict->_simulator_output = $previous_output;
			unset($out);
		}
	}
};

abstract class Extension_DevblocksEventCondition extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.condition', $as_instances);
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($extensions, 'params->[label]');
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict);
};

abstract class Extension_DevblocksEventAction extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.action', $as_instances);
		if($as_instances)
			DevblocksPlatform::sortObjects($extensions, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($extensions, 'params->[label]');
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {}
	abstract function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict);
}

abstract class DevblocksHttpResponseListenerExtension extends DevblocksExtension {
	function run(DevblocksHttpResponse $request, Smarty $tpl) {
	}
};

abstract class Extension_DevblocksStorageEngine extends DevblocksExtension {
	protected $_options = array();

	abstract function renderConfig(Model_DevblocksStorageProfile $profile);
	abstract function saveConfig(Model_DevblocksStorageProfile $profile);
	abstract function testConfig();
	
	abstract function exists($namespace, $key);
	abstract function put($namespace, $id, $data);
	abstract function get($namespace, $key, &$fp=null);
	abstract function delete($namespace, $key);
	
	public function setOptions($options=array()) {
		if(is_array($options))
			$this->_options = $options;
	}

	protected function escapeNamespace($namespace) {
		return strtolower(DevblocksPlatform::strAlphaNum($namespace, '\_'));
	}
};

abstract class Extension_DevblocksStorageSchema extends DevblocksExtension {
	abstract function render();
	abstract function renderConfig();
	abstract function saveConfig();
	
	public static function getActiveStorageProfile() {}

	public static function get($object, &$fp=null) {}
	public static function put($id, $contents, $profile=null) {}
	public static function delete($ids) {}
	public static function archive($stop_time=null) {}
	public static function unarchive($stop_time=null) {}
	
	protected function _stats($table_name) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$stats = array();
		
		$results = $db->GetArray(sprintf("SELECT storage_extension, storage_profile_id, count(id) as hits, sum(storage_size) as bytes FROM %s GROUP BY storage_extension, storage_profile_id ORDER BY storage_extension",
			$table_name
		));
		foreach($results as $result) {
			$stats[$result['storage_extension'].':'.intval($result['storage_profile_id'])] = array(
				'storage_extension' => $result['storage_extension'],
				'storage_profile_id' => $result['storage_profile_id'],
				'count' => intval($result['hits']),
				'bytes' => intval($result['bytes']),
			);
		}
		
		return $stats;
	}
	
};

abstract class DevblocksControllerExtension extends DevblocksExtension implements DevblocksHttpRequestHandler {
	public function handleRequest(DevblocksHttpRequest $request) {}
	public function writeResponse(DevblocksHttpResponse $response) {}
};

abstract class DevblocksEventListenerExtension extends DevblocksExtension {
    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {}
};

interface DevblocksHttpRequestHandler {
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request);
	public function writeResponse(DevblocksHttpResponse $response);
};

class DevblocksHttpRequest extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
};

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
};

abstract class DevblocksHttpIO {
	public $path = array();
	public $query = array();
	
	/**
	 * Enter description here...
	 *
	 * @param array $path
	 */
	function __construct($path,$query=array()) {
		$this->path = $path;
		$this->query = $query;
	}
};

class _DevblocksSortHelper {
	private static $_sortOn = ''; 
	
	static function sortByNestedMember($a, $b) {
		$props = explode('->', self::$_sortOn);
		
		$a_test = $a;
		$b_test = $b;
		
		foreach($props as $prop) {
			$is_index = false;
			
			if(@preg_match("#\[(.*?)\]#", $prop, $matches)) {
				$is_index = true;
				$prop = $matches[1];
			}
			
			if($is_index) {
				if(!isset($a_test[$prop]) || !isset($b_test[$prop]))
					return 0;
				
				$a_test = $a_test[$prop];
				$b_test = $b_test[$prop];
				
			} else {
				if(!isset($a_test->$prop) || !isset($b_test->$prop))
					return 0;
				
				$a_test = $a_test->$prop;
				$b_test = $b_test->$prop;
			}
		}
		
		if(is_numeric($a_test) && is_numeric($b_test)) {
			settype($a_test, 'integer');
			settype($b_test, 'integer');
			if($a_test==$b_test)
				return 0;
			return ($a_test > $b_test) ? 1 : -1;
			
		} else {
			if(!is_string($a_test) || !is_string($b_test))
				return 0;
			
			return strcasecmp($a_test, $b_test);
		}
	}
	
	static function sortObjects(&$array, $on, $ascending=true) {
		self::$_sortOn = $on;
		
		uasort($array, array('_DevblocksSortHelper', 'sortByNestedMember'));
		
		if(!$ascending)
			$array = array_reverse($array, true);
	}	
};