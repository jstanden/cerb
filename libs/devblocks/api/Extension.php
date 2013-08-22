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

interface IDevblocksHandler_Session {
	static function open($save_path, $session_name);
	static function close();
	static function read($id);
	static function write($id, $session_data);
	static function destroy($id);
	static function gc($maxlifetime);
	static function getAll();
	static function destroyAll();
};

interface IDevblocksContextPeek {
	function renderPeekPopup($context_id=0, $view_id='');
}

interface IDevblocksContextImport {
	function importGetKeys();
	function importKeyValue($key, $value);
	function importSaveObject(array $fields, array $custom_fields, array $meta);
}

interface IDevblocksContextProfile {
	function profileGetUrl($context_id);
}

abstract class Extension_DevblocksContext extends DevblocksExtension {
	static $_changed_contexts = array();
	
	static function markContextChanged($context, $context_ids) {
		if(!is_array($context_ids))
			$context_ids = array($context_ids);
		
		if(!isset(self::$_changed_contexts[$context]))
			self::$_changed_contexts[$context] = array();
		
		self::$_changed_contexts[$context] = array_merge(self::$_changed_contexts[$context], $context_ids);
	}
	
	static function shutdownTriggerChangedContextsEvents() {
		$eventMgr = DevblocksPlatform::getEventService();
		
		if(is_array(self::$_changed_contexts))
		foreach(self::$_changed_contexts as $context => $context_ids) {
			$eventMgr->trigger(
				new Model_DevblocksEvent(
					'context.update',
					array(
						'context' => $context,
						'context_ids' => $context_ids,
					)
				)
			);
		}
		
		self::$_changed_contexts = array();
	}
	
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
	
	public static function getByAlias($alias, $as_instance=false) {
		$contexts = self::getAll(false);
		
		if(is_array($contexts))
		foreach($contexts as $ctx_id => $ctx) { /* @var $ctx DevblocksExtensionManifest */
			if(isset($ctx->params['alias']) && 0 == strcasecmp($ctx->params['alias'], $alias)) {
				if($as_instance) {
					return $ctx->createInstance();
				} else {
					return $ctx;
				}
			}
		}
		
		return null;
	}
	
	public static function getByViewClass($view_class, $as_instance=false) {
		$contexts = self::getAll(false);
		
		if(is_array($contexts))
		foreach($contexts as $ctx_id => $ctx) { /* @var $ctx DevblocksExtensionManifest */
			if(isset($ctx->params['view_class']) && 0 == strcasecmp($ctx->params['view_class'], $view_class)) {
				if($as_instance) {
					return $ctx->createInstance();
				} else {
					return $ctx;
				}
			}
		}
		
		return null;
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
	public function getSearchView($view_id=null) {
		if(empty($view_id)) {
			$view_id = sprintf("search_%s",
				str_replace('.','_',DevblocksPlatform::strToPermalink($this->id))
			);
		}
		
		if(null == ($view = C4_AbstractViewLoader::getView($view_id))) {
			$view = $this->getChooserView($view_id); /* @var $view C4_AbstractViewModel */
		}
		
		$view->name = 'Search Results';
		$view->renderFilters = false;
		$view->is_ephemeral = false;
		
		C4_AbstractViewLoader::setView($view_id, $view);
		
		return $view;
	}
	abstract function getChooserView($view_id=null);
	function getViewClass() {
		return @$this->manifest->params['view_class'];
	}
	abstract function getView($context=null, $context_id=null, $options=array());
	function lazyLoadContextValues($token, $dictionary) { return array(); }
	
	protected function _lazyLoadCustomFields($context, $context_id) {
		$fields = DAO_CustomField::getByContext($context);
		$token_values['custom'] = array();
		$field_values = array();

		$results = DAO_CustomFieldValue::getValuesByContextIds($context, $context_id);
		if(is_array($results))
			$field_values = array_shift($results);
		
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
	
	protected function _getTokenLabelsFromCustomFields($fields, $prefix) {
		$labels = array();
		$fieldsets = DAO_CustomFieldset::getAll();
		
		if(is_array($fields))
		foreach($fields as $cf_id => $field) {
			$fieldset = $field->custom_fieldset_id ? @$fieldsets[$field->custom_fieldset_id] : null;
		
			$labels['custom_'.$cf_id] = sprintf("%s%s%s",
				$prefix,
				($fieldset ? ($fieldset->name . ':') : ''),
				$field->name
			);
		}
		
		return $labels;
	}
	
	protected function _getImportCustomFields($fields, &$keys) {
		if(is_array($fields))
		foreach($fields as $token => $cfield) {
			if('cf_' != substr($token, 0, 3))
				continue;
			
			$cfield_id = intval(substr($token, 3));
			
			$keys['cf_' . $cfield_id] = array(
				'label' => $cfield->db_label,
				'type' => $cfield->type,
				'param' => $cfield->token,
			);
		}
		
		return true;
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
				
				$conditions[$token] = array(
					'label' => $label,
					'type' => $cfield->type,
				);
				
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
			'_calendar_availability' => array('label' => '(Calendar availability)', 'type' => ''),
			'_custom_script' => array('label' => '(Custom script)', 'type' => ''),
			'_month_of_year' => array('label' => '(Month of year)', 'type' => ''),
			'_day_of_week' => array('label' => '(Day of week)', 'type' => ''),
			'_time_of_day' => array('label' => '(Time of day)', 'type' => ''),
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
			case '_calendar_availability':
				// Get readable by VA
				$calendars = DAO_Calendar::getReadableByActor(array(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $trigger->virtual_attendant_id));
				$tpl->assign('calendars', $calendars);
				
				return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_calendar_availability.tpl');
				break;
				
			case '_custom_script':
				return $tpl->display('devblocks:cerberusweb.core::internal/decisions/conditions/_custom_script.tpl');
				break;
				
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
					switch(@$condition['type']) {
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
							if(@substr($condition['type'],0,4) == 'ctx_') {
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
		
		$logger->info('');
		$logger->info(sprintf("Checking condition '%s'...", $token));
		
		// Built-in conditions
		switch($token) {
			case '_calendar_availability':
				if(false == (@$calendar_id = $params['calendar_id']))
					return false;
				
				@$is_available = $params['is_available'];
				@$from = $params['from'];
				@$to = $params['to'];
				
				if(false == ($calendar = DAO_Calendar::get($calendar_id)))
					return false;
				
				@$cal_from = strtotime("today", strtotime($from));
				@$cal_to = strtotime("tomorrow", strtotime($to));
				
				$calendar_events = $calendar->getEvents($cal_from, $cal_to);
				$availability = $calendar->computeAvailability($cal_from, $cal_to, $calendar_events);

				$pass = ($is_available == $availability->isAvailableBetween(strtotime($from), strtotime($to)));
				break;
				
			case '_custom_script':
				@$tpl = DevblocksPlatform::importVar($params['tpl'],'string','');
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($tpl, $dict);

				if(false === $value) {
					$logger->error(sprintf("[Script] Syntax error:\n\n%s",
						implode("\n", $tpl_builder->getErrors())
					));
					return false;
				}
				
				$value = trim($value);
				
				@$not = (substr($params['oper'],0,1) == '!');
				@$oper = ltrim($params['oper'],'!');
				@$param_value = $params['value'];
				
				$logger->info(sprintf("Script: `%s` %s%s `%s`",
					$value,
					(!empty($not) ? 'not ' : ''),
					$oper,
					$param_value
				));
				
				switch($oper) {
					case 'is':
						$pass = (0==strcasecmp($value,$param_value));
						break;
					case 'like':
						$regexp = DevblocksPlatform::strToRegExp($param_value);
						$pass = @preg_match($regexp, $value);
						break;
					case 'contains':
						$pass = (false !== stripos($value, $param_value)) ? true : false;
						break;
					case 'regexp':
						$pass = @preg_match($param_value, $value);
						break;
				}
				break;
				
			case '_month_of_year':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$months = DevblocksPlatform::importVar($params['month'],'array',array());
				
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
					switch(@$condition['type']) {
						case Model_CustomField::TYPE_CHECKBOX:
							$bool = intval($params['bool']);
							$pass = !empty($value) == $bool;
							$logger->info(sprintf("Checkbox: %s = %s",
								(!empty($value) ? 'true' : 'false'),
								(!empty($bool) ? 'true' : 'false')
							));
							break;
							
						case Model_CustomField::TYPE_DATE:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							$oper = 'between';
							
							$from = strtotime($params['from']);
							$to = strtotime($params['to']);
							
							$logger->info(sprintf("Date: `%s` %s%s `%s` and `%s`",
								DevblocksPlatform::strPrettyTime($value),
								(!empty($not) ? 'not ' : ''),
								$oper,
								DevblocksPlatform::strPrettyTime($from),
								DevblocksPlatform::strPrettyTime($to)
							));
							
							switch($oper) {
								case 'between':
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
							@$param_value = $params['value'];
							
							$logger->info(sprintf("Text: `%s` %s%s `%s`",
								$value,
								(!empty($not) ? 'not ' : ''),
								$oper,
								$param_value
							));
							
							switch($oper) {
								case 'is':
									$pass = (0==strcasecmp($value,$param_value));
									break;
								case 'like':
									$regexp = DevblocksPlatform::strToRegExp($param_value);
									$pass = @preg_match($regexp, $value);
									break;
								case 'contains':
									$pass = (false !== stripos($value, $param_value)) ? true : false;
									break;
								case 'regexp':
									$pass = @preg_match($param_value, $value);
									break;
							}
							
							// Handle operator negation
							break;
							
						case Model_CustomField::TYPE_NUMBER:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							@$desired_value = intval($params['value']);
							
							$logger->info(sprintf("Number: %d %s%s %d",
								$value,
								(!empty($not) ? 'not ' : ''),
								$oper,
								$desired_value
							));
							
							switch($oper) {
								case 'is':
									$pass = intval($value)==$desired_value;
									break;
								case 'gt':
									$pass = intval($value) > $desired_value;
									break;
								case 'lt':
									$pass = intval($value) < $desired_value;
									break;
							}
							break;
							
						case Model_CustomField::TYPE_DROPDOWN:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							$desired_values = isset($params['values']) ? $params['values'] : array();
							
							$logger->info(sprintf("`%s` %s%s `%s`",
								$value,
								(!empty($not) ? 'not ' : ''),
								$oper,
								implode('; ', $desired_values)
							));
							
							if(!isset($desired_values) || !is_array($desired_values)) {
								$pass = false;
								break;
							}
							
							switch($oper) {
								case 'in':
									$pass = false;
									if(in_array($value, $desired_values)) {
										$pass = true;
									}
									break;
							}
							break;
							
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							if(preg_match("#(.*?_custom)_(\d+)#", $token, $matches) && 3 == count($matches)) {
								$value_token = $matches[1];
								$value_field = $dict->$value_token;
								@$value = $value_field[$matches[2]];
							}
							
							if(!is_array($value) || !isset($params['values']) || !is_array($params['values'])) {
								$pass = false;
								break;
							}
							
							$logger->info(sprintf("Multi-checkbox: `%s` %s%s `%s`",
								implode('; ', $params['values']),
								(!empty($not) ? 'not ' : ''),
								$oper,
								implode('; ', $value)
							));
							
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
							if(@substr($condition['type'],0,4) == 'ctx_') {
								$count = (isset($dict->$token) && is_array($dict->$token)) ? count($dict->$token) : 0;
								
								$not = (substr($params['oper'],0,1) == '!');
								$oper = ltrim($params['oper'],'!');
								@$desired_count = intval($params['value']);

								$logger->info(sprintf("Count: %d %s%s %d",
									$count,
									$not,
									$oper,
									$desired_count
								));
								
								switch($oper) {
									case 'is':
										$pass = $count==$desired_count;
										break;
									case 'gt':
										$pass = $count > $desired_count;
										break;
									case 'lt':
										$pass = $count < $desired_count;
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
			
		$logger->info(sprintf("  ... %s", ($pass ? 'PASS' : 'FAIL')));
		
		return $pass;
	}
	
	function getActions($trigger) { /* @var $trigger Model_TriggerEvent */
		$actions = array(
			'_run_behavior' => array('label' => '(Run behavior)'),
			'_schedule_behavior' => array('label' => '(Schedule behavior)'),
			'_set_custom_var' => array('label' => '(Set a custom placeholder)'),
			'_unschedule_behavior' => array('label' => '(Unschedule behavior)'),
		);
		$custom = $this->getActionExtensions();
		
		if(!empty($custom) && is_array($custom))
			$actions = array_merge($actions, $custom);
		
		// Trigger variables
		
		if(is_array($trigger->variables))
		foreach($trigger->variables as $key => $var) {
			$actions[$key] = array('label' => '(Set variable: ' . $var['label'] . ')');
		}
		
		// Add plugin extensions
		
		$manifests = Extension_DevblocksEventAction::getAll(false, $trigger->event_point);
		
		// Filter extensions by VA permissions
		
		$va = $trigger->getVirtualAttendant();
		
		@$actions_mode = $va->params['actions']['mode'];
		@$actions_items = $va->params['actions']['items'];
		
		switch($actions_mode) {
			case 'allow':
				$manifests = array_intersect_key($manifests, array_flip($actions_items));
				break;
				
			case 'deny':
				$manifests = array_diff_key($manifests, array_flip($actions_items));
				break;
		}
		
		if(is_array($manifests))
		foreach($manifests as $manifest) {
			$actions[$manifest->id] = array('label' => $manifest->params['label']);
		}

		// Sort by label
		
		DevblocksPlatform::sortObjects($actions, '[label]');
		
		return $actions;
	}
	
	abstract function getActionExtensions();
	abstract function renderActionExtension($token, $trigger, $params=array(), $seq=null);
	abstract function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict);
	protected function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {}
	function renderSimulatorTarget($trigger, $event_model) {}
	
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
				case '_set_custom_var':
					$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_custom_var.tpl');
					break;
					
				case '_run_behavior':
					DevblocksEventHelper::renderActionRunBehavior($trigger);
					break;
				
				case '_schedule_behavior':
					$dates = array();
					$conditions = $this->getConditions($trigger);
					foreach($conditions as $key => $data) {
						if(isset($data['type']) && $data['type'] == Model_CustomField::TYPE_DATE)
							$dates[$key] = $data['label'];
					}
					$tpl->assign('dates', $dates);
				
					DevblocksEventHelper::renderActionScheduleBehavior($trigger);
					break;
					
				case '_unschedule_behavior':
					DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
					break;
					
				default:
					// Variables
					if(substr($token,0,4) == 'var_') {
						@$var = $trigger->variables[$token];
						
						switch(@$var['type']) {
							case Model_CustomField::TYPE_CHECKBOX:
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
								break;
							case Model_CustomField::TYPE_DATE:
								// Restricted to VA-readable calendars
								$calendars = DAO_Calendar::getReadableByActor(array(CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT, $trigger->virtual_attendant_id));
								$tpl->assign('calendars', $calendars);
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
								break;
							case Model_CustomField::TYPE_NUMBER:
								return $tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
								break;
							case Model_CustomField::TYPE_SINGLE_LINE:
								return DevblocksEventHelper::renderActionSetVariableString($this->getLabels());
								break;
							case Model_CustomField::TYPE_WORKER:
								return DevblocksEventHelper::renderActionSetVariableWorker($token, $trigger, $params);
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
				case '_set_custom_var':
					@$var = $params['var'];
					
					return sprintf(">>> Setting custom variable {{%s}}:\n%s\n\n",
						$var,
						$dict->$var
					);
					break;
					
				case '_run_behavior':
					return DevblocksEventHelper::simulateActionRunBehavior($params, $dict);
					break;
					
				case '_schedule_behavior':
					return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
					break;
					
				case '_unschedule_behavior':
					return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
					break;
					
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
				case '_set_custom_var':
					$tpl_builder = DevblocksPlatform::getTemplateBuilder();
					
					@$var = $params['var'];
					@$value = $params['value'];
					
					if(!empty($var) && !empty($value))
						$dict->$var = $tpl_builder->build($value, $dict);
					
					if($dry_run) {
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					} else {
						return;
					}
					break;
					
				case '_run_behavior':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionRunBehavior($params, $dict);
					break;
					
				case '_schedule_behavior':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionScheduleBehavior($params, $dict);
					break;
					
				case '_unschedule_behavior':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionUnscheduleBehavior($params, $dict);
					break;
					
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
			
			$node = array_pop($log);
			
			if(!empty($node) && isset($nodes[$node])) {
				$output = array(
					'action' => $nodes[$node]->title,
					'title' => $all_actions[$token]['label'],
					'content' => $out,
				);
				
				$previous_output = $dict->_simulator_output;
				$previous_output[] = $output;
				$dict->_simulator_output = $previous_output;
				unset($out);
			}
		}
	}
};

abstract class Extension_DevblocksEventCondition extends DevblocksExtension {
	public static function getAll($as_instances=false, $for_event=null) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.condition', false);
		$results = array();
		
		foreach($extensions as $ext_id => $ext) {
			// If the condition doesn't specify event filters, add to everything
			if(!isset($ext->params['events'][0])) {
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				
			} else {
				// Loop through the patterns
				foreach(array_keys($ext->params['events'][0]) as $evt_pattern) {
					$evt_pattern = DevblocksPlatform::strToRegExp($evt_pattern);
					
					if(preg_match($evt_pattern, $for_event))
						$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				}
			}
		}
		
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($results, 'params->[label]');
		
		return $results;
	}
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict);
};

abstract class Extension_DevblocksEventAction extends DevblocksExtension {
	public static function getAll($as_instances=false, $for_event=null) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.action', false);
		$results = array();
		
		foreach($extensions as $ext_id => $ext) {
			// If the action doesn't specify event filters, add to everything
			if(!isset($ext->params['events'][0])) {
				$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				
			} else {
				// Loop through the patterns
				foreach(array_keys($ext->params['events'][0]) as $evt_pattern) {
					$evt_pattern = DevblocksPlatform::strToRegExp($evt_pattern);
					
					if(preg_match($evt_pattern, $for_event))
						$results[$ext_id] = $as_instances ? $ext->createInstance() : $ext;
				}
			}
		}
			
		if($as_instances)
			DevblocksPlatform::sortObjects($results, 'manifest->params->[label]');
		else
			DevblocksPlatform::sortObjects($results, 'params->[label]');
		
		return $results;
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
				if(!isset($a_test[$prop]) && !isset($b_test[$prop]))
					return 0;
				
				@$a_test = $a_test[$prop];
				@$b_test = $b_test[$prop];
				
			} else {
				if(!isset($a_test->$prop) && !isset($b_test->$prop)) {
					return 0;
				}
				
				@$a_test = $a_test->$prop;
				@$b_test = $b_test->$prop;
			}
		}
		
		if(is_numeric($a_test) && is_numeric($b_test)) {
			settype($a_test, 'float');
			settype($b_test, 'float');
			
			if($a_test==$b_test)
				return 0;
			
			return ($a_test > $b_test) ? 1 : -1;
			
		} else {
			$a_test = is_null($a_test) ? '' : $a_test;
			$b_test = is_null($b_test) ? '' : $b_test;
			
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