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
	function DevblocksExtension($manifest) { /* @var $manifest DevblocksExtensionManifest */
        if(empty($manifest)) return;
        
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
	public static function getAll($as_instances=false) {
		$contexts = DevblocksPlatform::getExtensions('devblocks.context', $as_instances);
		if($as_instances)
			uasort($contexts, create_function('$a, $b', "return strcasecmp(\$a->manifest->name,\$b->manifest->name);\n"));
		else
			uasort($contexts, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
		return $contexts;
	}
	
	/*
	 * Lazy loader + cache
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
};

abstract class Extension_DevblocksEvent extends DevblocksExtension {
	const POINT = 'devblocks.event'; 
	
	private $_labels = array();
	private $_values = array();
	
	public static function getAll($as_instances=false) {
		$events = DevblocksPlatform::getExtensions('devblocks.event', $as_instances);
		if($as_instances)
			uasort($events, create_function('$a, $b', "return strcasecmp(\$a->manifest->name,\$b->manifest->name);\n"));
		else
			uasort($events, create_function('$a, $b', "return strcasecmp(\$a->name,\$b->name);\n"));
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
	
	private function _importLabels($labels) {
		uasort($labels, create_function('$a, $b', "return strcasecmp(\$a,\$b);\n"));
		return $labels;
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
		$this->_labels = $this->_importLabels($labels);
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
		//var_dump($manifests);
		foreach($manifests as $manifest) {
			$conditions[$manifest->id] = array('label' => $manifest->params['label']);
		}
			
		uasort($conditions, create_function('$a, $b', "return strcasecmp(\$a['label'],\$b['label']);\n"));
			
		return $conditions;
	}
	
	abstract function getConditionExtensions();
	abstract function renderConditionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runConditionExtension($token, $trigger, $params, $values);
	
	function renderCondition($token, $trigger, $params=array(), $seq=null) {
		$conditions = $this->getConditions($trigger);
		$extensions = $this->getConditionExtensions();
		
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
							break;
					}
				}
				break;
		}
	}
	
	function runCondition($token, $trigger, $params, $values) {
		$logger = DevblocksPlatform::getConsoleLog('Assistant');
		$conditions = $this->getConditions($trigger);
		$extensions = $this->getConditionExtensions();
		$not = false;
		$pass = true;
		
		switch($token) {
			case '_month_of_year':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				switch($oper) {
					case 'is':
						$month = date('n');
						$pass = in_array($month, $params['month']);
						break;
				}
				break;
			case '_day_of_week':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				switch($oper) {
					case 'is':
						$today = date('N');
						$pass = in_array($today, $params['day']);
						break;
				}
				break;
			case '_time_of_day':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				switch($oper) {
					case 'between':
						$now = strtotime('now');
						$from = strtotime($params['from']);
						$to = strtotime($params['to']);
						if($to < $from)
							$to += 86400; // +1 day
						$pass = ($now >= $from && $now <= $to) ? true : false;
						break;
				}
				break;
		
			default:
				// Operators
				if(null != (@$condition = $conditions[$token])) {
					if(null == (@$value = $values[$token])) {
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
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							if(!is_array($value))
								$value = empty($value) ? array() : array($value);
							
							if(!is_array($params['worker_id']))
								return false;
							
							switch($oper) {
								case 'in':
									$pass = false;
									foreach($params['worker_id'] as $v) {
										if(in_array($v, $value)) {
											$pass = true;
											break;
										}
									}
									break;
							}
							break;
							
						default:
							if(isset($extensions[$token])) {
								$pass = $this->runConditionExtension($token, $trigger, $params, $values);
							} else {
								if(null != ($ext = DevblocksPlatform::getExtension($token, true))
									&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */ 
									$pass = $ext->run($token, $trigger, $params, $values);
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
		//var_dump($manifests);
		foreach($manifests as $manifest) {
			$actions[$manifest->id] = array('label' => $manifest->params['label']);
		}
			
		uasort($actions, create_function('$a, $b', "return strcasecmp(\$a['label'],\$b['label']);\n"));
			
		return $actions;
	}
	
	abstract function getActionExtensions();
	abstract function renderActionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runActionExtension($token, $trigger, $params, &$values);
	
	function renderAction($token, $trigger, $params=array(), $seq=null) {
		$actions = $this->getActionExtensions();
		
		$tpl = DevblocksPlatform::getTemplateService();
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
	
	function runAction($token, $trigger, $params, &$values) {
		$actions = $this->getActionExtensions();
		
		if(null != (@$action = $actions[$token])) {
			$this->runActionExtension($token, $trigger, $params, $values);
			
		} else {
			switch($token) {
				default:
					// Variables
					if(substr($token,0,4) == 'var_') {
						return DevblocksEventHelper::runActionSetVariable($token, $trigger, $params, $values);
					
					} else {
						// Plugins
						if(null != ($ext = DevblocksPlatform::getExtension($token, true))
							&& $ext instanceof Extension_DevblocksEventAction) { /* @var $ext Extension_DevblocksEventAction */ 
							return $ext->run($token, $trigger, $params, $values);
						}
					}
					break;
			}
		}
			
	}
	
};

class DevblocksEventHelper {
	/*
	 * Action: Custom Fields
	 */
	static function getActionCustomFields($context) {
		$actions = array();
		
		// Set custom fields
		$custom_fields = DAO_CustomField::getByContext($context);
		foreach($custom_fields as $field_id => $field) {
			$actions['set_cf_' . $field_id] = array(
				'label' => 'Set ' . mb_convert_case($field->name, MB_CASE_LOWER),
				'type' => $field->type,
			);
		}
		
		return $actions;
	}
	
	static function renderActionSetCustomField(Model_CustomField $custom_field) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_string.tpl');
				break;
				
			case Model_CustomField::TYPE_MULTI_LINE:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_clob.tpl');
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
				break;
				
			case Model_CustomField::TYPE_DATE:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				$tpl->assign('options', $custom_field->options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_dropdown.tpl');
				$tpl->clearAssign('options');
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('options', $custom_field->options);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_multi_checkbox.tpl');
				$tpl->clearAssign('options');
				break;
				
			case Model_CustomField::TYPE_WORKER:
				$workers = DAO_Worker::getAllActive();
				$tpl->assign('workers', $workers);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
				break;
		}		
	}	
	
	static function runActionSetCustomField(Model_CustomField $custom_field, $value_key, $params, &$values, $context, $context_id) {
		@$field_id = $custom_field->id;
		
		// [TODO] Log
		if(empty($field_id) || empty($context) || empty($context_id))
			return;
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_URL:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $values);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);

				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $value;
					$values[$value_key][$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				$value = $params['value'];
				$value = strtotime($value);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);
				
				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $value;
					$values[$value_key][$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$opts = $params['values'];
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $opts, true);

				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = implode(',',$opts);
					$values[$value_key][$field_id] = $opts;
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_id = $params['worker_id'];
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $worker_id);
				
				if(!empty($value_key)) {
					$values[$value_key.'_'.$field_id] = $worker_id;
					$values[$value_key][$field_id] = $worker_id;
				}
				break;
				
			default:
				$this->runActionExtension($token, $trigger, $params, $values);
				break;	
		}		
	}
	
	/*
	 * Action: Set variable (string)
	 */
	
	static function renderActionSetVariableString($labels) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('token_labels', $labels);
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
	}
	
	static function runActionSetVariable($token, $trigger, $params, &$values) {
		@$var = $trigger->variables[$token];
		
		$value = null;
		
		if(empty($var) || !is_array($var))
			return;
		
		switch($var['type']) {
			case Model_CustomField::TYPE_CHECKBOX:
				$value = (isset($params['value']) && !empty($params['value'])) ? true : false;
				break;
				
			case Model_CustomField::TYPE_DATE:
				if(!isset($params['value']))
					break;
				
				$value = is_numeric($params['value']) ? $params['value'] : @strtotime($params['value']);
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$value = intval($params['value']);
				break;
				
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
				if(!isset($params['value']))
					break;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $values);
				break;
		}

		$values[$token] = $value; 
	}
	
	/*
	 * Action: Schedule Behavior
	 */
	
	static function renderActionScheduleBehavior($context, $context_id, $event_point) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$macros = DAO_TriggerEvent::getByOwner($context, $context_id, $event_point);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_schedule_behavior.tpl');
	}
	
	static function runActionScheduleBehavior($params, $values, $context, $context_id) {
		@$behavior_id = $params['behavior_id'];
		@$run_date = $params['run_date'];
		@$on_dupe = $params['on_dupe'];
		
		// [TODO] Relative dates
		@$run_timestamp = strtotime($run_date);
		
		if(empty($behavior_id))
			return FALSE;
		
		switch($on_dupe) {
			// Only keep first
			case 'first':
				// Keep the first, delete everything else, and don't add a new one
				$behaviors = DAO_ContextScheduledBehavior::getByContext($context, $context_id);
				$found_first = false;
				foreach($behaviors as $k => $behavior) { /* @var $behavior Model_ContextScheduledBehavior */
					if($behavior->behavior_id == $behavior_id) {
						if($found_first) {
							DAO_ContextScheduledBehavior::delete($k);
						}
						$found_first = $k;
					}
				}
				
				// If we already have one, don't make a new one.
				if($found_first)
					return $found_first;
				
				break;

			// Only keep latest
			case 'last':
				// Delete everything prior so we only have the new one below
				DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id);
				break;
			
			// Allow dupes
			default:
				// Do nothing
				break;
		}
		
		$fields = array(
			DAO_ContextScheduledBehavior::CONTEXT => $context,
			DAO_ContextScheduledBehavior::CONTEXT_ID => $context_id,
			DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
			DAO_ContextScheduledBehavior::RUN_DATE => intval($run_timestamp),
		);
		return DAO_ContextScheduledBehavior::create($fields);
	}
	
	/*
	 * Action: Unschedule Behavior
	 */
	
	static function renderActionUnscheduleBehavior($context, $context_id, $event_point) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$macros = DAO_TriggerEvent::getByOwner($context, $context_id, $event_point);
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_unschedule_behavior.tpl');
	}
	
	static function runActionUnscheduleBehavior($params, $values, $context, $context_id) {
		@$behavior_id = $params['behavior_id'];
		
		if(empty($behavior_id))
			return FALSE;
		
		return DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id);
	}
	
	/*
	 * Action: Create Comment
	 */
	
	static function renderActionCreateComment() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_comment.tpl');
	}
	
	static function runActionCreateComment($params, $values, $context, $context_id) {
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		$fields = array(
			DAO_Comment::ADDRESS_ID => 0,
			DAO_Comment::CONTEXT => $context,
			DAO_Comment::CONTEXT_ID => $context_id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $content,
		);
		$comment_id = DAO_Comment::create($fields);
		
		return $comment_id;
	}
	
	static function renderActionScheduleTicketReply() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_schedule_email_recipients.tpl');
	}
	
	static function runActionScheduleTicketReply($params, $values, $ticket_id, $message_id) {
		@$delivery_date_relative = $params['delivery_date'];
		
		if(false == ($delivery_date = strtotime($delivery_date_relative)))
			$delivery_date = time();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		$fields = array(
			DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
			DAO_MailQueue::IS_QUEUED => 1,
			//DAO_MailQueue::HINT_TO => implode($values['recipients']),
			DAO_MailQueue::HINT_TO => '(recipients)',
			DAO_MailQueue::SUBJECT => $values['ticket_subject'],
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode(array(
				'in_reply_message_id' => $message_id,				
			)),
			DAO_MailQueue::TICKET_ID => $ticket_id,
			DAO_MailQueue::WORKER_ID => 0,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::QUEUE_DELIVERY_DATE => $delivery_date,
		);
		$queue_id = DAO_MailQueue::create($fields);
	}
	
	static function renderActionSetTicketOwner() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAllActive());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
	}
	
	static function runActionSetTicketOwner($params, $values, $ticket_id) {
		@$owner_id = intval($params['worker_id']);
		$fields = array(
			DAO_Ticket::OWNER_ID => $owner_id,
		);
		DAO_Ticket::update($ticket_id, $fields);
	}
	
	static function renderActionAddWatchers() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_add_watchers.tpl');
	}
	
	static function runActionAddWatchers($params, $values, $context, $context_id) {
		@$worker_ids = $params['worker_id'];
		
		if(!is_array($worker_ids) || empty($worker_ids))
			return;

		CerberusContexts::addWatchers($context, $context_id, $worker_ids);
	}
	
	/*
	 * Action: Create Notification
	 */
	
	static function renderActionCreateNotification() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_notification.tpl');
	}
	
	static function runActionCreateNotification($params, $values, $context, $context_id) {
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();

		// Watchers?
		if(isset($params['notify_watchers']) && !empty($params['notify_watchers'])) {
			// [TODO] Lazy load from values (and set back to)
			$watchers = CerberusContexts::getWatchers($context, $context_id);
			$notify_worker_ids = array_merge($notify_worker_ids, array_keys($watchers));
		}

		if(!is_array($notify_worker_ids) || empty($notify_worker_ids))
			return;
		
		if(empty($context))
			return;
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $values);
		
		foreach($notify_worker_ids as $notify_worker_id) {
			$fields = array(
				DAO_Notification::CONTEXT => $context,
				DAO_Notification::CONTEXT_ID => $context_id,
				DAO_Notification::WORKER_ID => $notify_worker_id,
				DAO_Notification::CREATED_DATE => time(),
				DAO_Notification::MESSAGE => $content,
				DAO_Notification::URL => '',
			);
			$notification_id = DAO_Notification::create($fields);
			
			DAO_Notification::clearCountCache($notify_worker_id);
		}
		
		return $notification_id;
	}
	
	/*
	 * Action: Create Task
	 */
	
	static function renderActionCreateTask() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_task.tpl');
	}
	
	static function runActionCreateTask($params, $values, $context=null, $context_id=null) {
		$due_date = intval(@strtotime($params['due_date']));
	
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$title = $tpl_builder->build($params['title'], $values);
		$comment = $tpl_builder->build($params['comment'], $values);
		
		$fields = array(
			DAO_Task::TITLE => $title,
			DAO_Task::UPDATED_DATE => time(),
			DAO_Task::DUE_DATE => $due_date,
		);
		$task_id = DAO_Task::create($fields);

		// Watchers
		if(isset($params['worker_id']) && !empty($params['worker_id']))
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $task_id, $params['worker_id']);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::ADDRESS_ID => 0,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
				DAO_Comment::CONTEXT_ID => $task_id,
				DAO_Comment::CREATED => time(),
			);
			
			// Notify
			@$notify_worker_ids = $params['notify_worker_id'];
			DAO_Comment::create($fields, $notify_worker_ids);
		}
		
		// Connection
		if(!empty($context) && !empty($context_id))
			DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $task_id, $context, $context_id);

		return $task_id;
	}
	
	/*
	 * Action: Create Ticket
	 */
	
	static function renderActionCreateTicket() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('groups', DAO_Group::getAll());
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_ticket.tpl');
	}
	
	static function runActionCreateTicket($params, $values) {
		@$group_id = $params['group_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$group_replyto = $group->getReplyTo();
			
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$requesters = $tpl_builder->build($params['requesters'], $values);
		$subject = $tpl_builder->build($params['subject'], $values);
		$content = $tpl_builder->build($params['content'], $values);
				
		$message = new CerberusParserMessage();
		$message->headers['date'] = date('r'); 
		$message->headers['to'] = $group_replyto->email;
		$message->headers['subject'] = $subject;
		$message->headers['message-id'] = CerberusApplication::generateMessageId();
		
		// Sender
		$fromList = imap_rfc822_parse_adrlist(rtrim($requesters,', '),'');
		
		if(empty($fromList) || !is_array($fromList)) {
			return; // abort with message
		}
		$from = array_shift($fromList);
		$from_address = $from->mailbox . '@' . $from->host;
		$message->headers['from'] = $from_address;

		$message->body = sprintf(
			"(... This message was manually created by a virtual assistant on behalf of the requesters ...)\r\n"
		);

		// [TODO] Custom fields
		
		// Parse
		$ticket_id = CerberusParser::parseMessage($message);
		$ticket = DAO_Ticket::get($ticket_id);
		
		// Add additional requesters to ticket
		if(is_array($fromList) && !empty($fromList))
		foreach($fromList as $requester) {
			if(empty($requester))
				continue;
			$host = empty($requester->host) ? 'localhost' : $requester->host;
			DAO_Ticket::createRequester($requester->mailbox . '@' . $host, $ticket_id);
		}
		
		// Worker reply
		$properties = array(
		    'message_id' => $ticket->first_message_id,
		    'ticket_id' => $ticket_id,
		    'subject' => $subject,
		    'content' => $content,
		    'worker_id' => 0, //$active_worker->id,
		);
		
		CerberusMail::sendTicketMessage($properties);
		
		return $ticket_id;
	}
	
	/*
	 * Action: Send Email
	 */
	
	function renderActionSendEmail() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_send_email.tpl');
	}
	
	function runActionSendEmail($params, $values) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$to = $params['to'];
		$subject = $tpl_builder->build($params['subject'], $values);
		$content = $tpl_builder->build($params['content'], $values);

		CerberusMail::quickSend(
			$to,
			$subject,
			$content
		);
	}
	
	/*
	 * Action: Relay Email
	 */
	
	// [TODO] Move this to an event parent so we can presume values
	
	function renderActionRelayEmail($filter_to_worker_ids=array(), $show=array('owner','watchers','workers'), $content_token='content') {
		$tpl = DevblocksPlatform::getTemplateService();
		$translate = DevblocksPlatform::getTranslationService();
		
		$tpl->assign('show', $show);
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$addresses = DAO_AddressToWorker::getAll();

		// Filter?
		if(!empty($filter_to_worker_ids)) {
			foreach($addresses as $k => $v) {
				if(!in_array($v->worker_id, $filter_to_worker_ids))
					unset($addresses[$k]);
			}
		}
		$tpl->assign('addresses', $addresses);
		
		$tpl->assign('default_content', vsprintf($translate->_('va.actions.ticket.relay.default_content'), $content_token));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_relay_email.tpl');
	}
	
	// [TODO] Move this to an event parent so we can presume values
	
	function runActionRelayEmail($params, $values, $context, $context_id, $group_id, $bucket_id, $message_id, $owner_id, $sender_email, $sender_name, $subject) {
		$logger = DevblocksPlatform::getConsoleLog('Attendant');
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
		
		if(empty($group_id) || null == ($group = DAO_Group::get($group_id))) {
			$logger->error("Can't load the ticket's group. Aborting action.");
			return;
		}
		
		$replyto = $group->getReplyTo($bucket_id);
		$relay_list = @$params['to'] or array();
		
		// Attachments
		$attachment_data = array();
		if(!empty($message_id)) {
			if(isset($params['include_attachments']) && !empty($params['include_attachments'])) {
				$attachment_data = DAO_AttachmentLink::getLinksAndAttachments(CerberusContexts::CONTEXT_MESSAGE, $message_id);
			}
		}

		// Owner
		if(isset($params['to_owner']) && !empty($params['to_owner'])) {
			if(!empty($owner_id)) {
				$relay_list[] = DAO_Worker::get($owner_id);
			}
		}
		
		// Watchers
		if(isset($params['to_watchers']) && !empty($params['to_watchers'])) {
			$watchers = CerberusContexts::getWatchers($context, $context_id);
			foreach($watchers as $watcher) { /* @var $watcher Model_Worker */
				$relay_list[] = $watcher;
			}
			unset($watchers);
		}
		
		// [TODO] Remove dupes
		
		if(is_array($relay_list))
		foreach($relay_list as $to) {
			try {
				if($to instanceof Model_Worker) {
					$worker = $to;
					$to_address = $worker->email;
					
				} else {
					// [TODO] Cache
					if(null == ($worker_address = DAO_AddressToWorker::getByAddress($to)))
						continue;
						
					if(null == ($worker = DAO_Worker::get($worker_address->worker_id)))
						continue;
					
					$to_address = $worker_address->address;
				}
				
				$mail = $mail_service->createMessage();
				
				$mail->setTo(array($to_address));
	
				$headers = $mail->getHeaders(); /* @var $headers Swift_Mime_Header */

				if(!empty($sender_name)) {
					$mail->setFrom($sender_email, $sender_name);
				} else {
					$mail->setFrom($sender_email);
				}
				
				$replyto_personal = $replyto->getReplyPersonal($worker);
				if(!empty($replyto_personal)) {
					$mail->setReplyTo($replyto->email, $replyto_personal);
				} else {
					$mail->setReplyTo($replyto->email);
				}
				
				if(!isset($params['subject']) || empty($params['subject'])) {
					$mail->setSubject($subject);
				} else {
					$subject = $tpl_builder->build($params['subject'], $values);
					$mail->setSubject($subject);
				}
	
				// Find the owner of this address and sign it.
				$sign = substr(md5($context.$context_id.$worker->pass),8,8);
				
				$headers->removeAll('message-id');
				$headers->addTextHeader('Message-Id', sprintf("<%s_%d_%d_%s@cerb5>", $context, $context_id, time(), $sign));
				$headers->addTextHeader('X-CerberusRedirect','1');
	
				$content = $tpl_builder->build($params['content'], $values);
				
				$mail->setBody($content);
				
				// Files
				if(!empty($attachment_data) && isset($attachment_data['attachments']) && !empty($attachment_data['attachments'])) {
					foreach($attachment_data['attachments'] as $file_id => $file) { /* @var $file Model_Attachment */
						if(false !== ($fp = DevblocksPlatform::getTempFile())) {
							if(false !== $file->getFileContents($fp)) {
								$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $file->mime_type);
								$attach->setFilename($file->display_name);
								$mail->attach($attach);
								fclose($fp);
							}
						}
					}
					
				}
				
				$result = $mailer->send($mail);
				unset($mail);
				
				if(!$result) {
					return false;
				}			
				
			} catch (Exception $e) {
				
			}
		}
	}
};

abstract class Extension_DevblocksEventCondition extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.condition', $as_instances);
		if($as_instances)
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->manifest->params['label'],\$b->manifest->params['label']);\n"));
		else
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->params['label'],\$b->params['label']);\n"));
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, $values);
}

abstract class Extension_DevblocksEventAction extends DevblocksExtension {
	public static function getAll($as_instances=false) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.action', $as_instances);
		if($as_instances)
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->manifest->params['label'],\$b->manifest->params['label']);\n"));
		else
			uasort($extensions, create_function('$a, $b', "return strcasecmp(\$a->params['label'],\$b->params['label']);\n"));
		return $extensions;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, &$values);
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
}

class DevblocksHttpRequest extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

class DevblocksHttpResponse extends DevblocksHttpIO {
	/**
	 * @param array $path
	 */
	function __construct($path, $query=array()) {
		parent::__construct($path, $query);
	}
}

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
}
