<?php
abstract class Extension_DevblocksEvent extends DevblocksExtension {
	const POINT = 'devblocks.event';
	
	private $_labels = [];
	private $_types = [];
	private $_values = [];
	
	private $_conditions_cache = [];
	private $_conditions_extensions_cache = [];
	
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false) {
		$events = DevblocksPlatform::getExtensions('devblocks.event', $as_instances);
		
		if(
			class_exists('DAO_CustomRecord', true)
			&& false != ($custom_records = DAO_CustomRecord::getAll())
			&& is_array($custom_records)
		) {
			foreach($custom_records as $custom_record) {
				$context_id = sprintf('contexts.custom_record.%d', $custom_record->id);
				$event_id = sprintf('event.macro.custom_record.%d', $custom_record->id);
				$manifest = new DevblocksExtensionManifest();
				$manifest->id = $event_id;
				$manifest->plugin_id = 'cerberusweb.core';
				$manifest->point = Extension_DevblocksEvent::POINT;
				$manifest->name = 'Record custom behavior on ' . DevblocksPlatform::strUpperFirst($custom_record->name, true);
				$manifest->file = 'api/events/macro/abstract_custom_record_macro.php';
				$manifest->class = 'Event_AbstractCustomRecord_' . $custom_record->id;
				$manifest->params = [
					'macro_context' => $context_id,
					'contexts' => [
						0 => [
							'cerberusweb.contexts.app' => '',
							'cerberusweb.contexts.group' => '',
							'cerberusweb.contexts.role' => '',
							'cerberusweb.contexts.worker' => '',
						],
					],
					'menu_key' => 'Records:Custom Behavior:' . DevblocksPlatform::strUpperFirst($custom_record->name, true),
					'options' => [
						0 => [
							'visibility' => '',
						],
					]
				];
				
				if($as_instances) {
					$events[$event_id] = $manifest->createInstance();
				} else {
					$events[$event_id] = $manifest;
				}
			}
		}
		
		if($as_instances)
			DevblocksPlatform::sortObjects($events, 'manifest->name');
		else
			DevblocksPlatform::sortObjects($events, 'name');
		return $events;
	}
	
	/**
	 * @internal
	 */
	public static function get($id, $as_instance=true) {
		$events = self::getAll(false);
		$id = strval($id);
		
		if(!isset($events[$id]))
			return null;
		
		$manifest = $events[$id]; /* @var $manifest DevblocksExtensionManifest */
		
		if($as_instance) {
			return $manifest->createInstance();
		} else {
			return $events[$id];
		}
		
		return null;
	}
	
	/**
	 * @internal
	 */
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
	
	/**
	 * @internal
	 */
	public static function getWithMacroContexts() {
		$macros = Extension_DevblocksEvent::getAll();
		
		$macros = array_filter($macros, function($event) {
			return array_key_exists('macro_context', $event->params);
		});
		
		return $macros;
	}
	
	/**
	 * @internal
	 */
	protected function _importLabelsTypesAsConditions($labels, $types) {
		$conditions = [];
		$custom_fields = DAO_CustomField::getAll();
		
		foreach($types as $token => $type) {
			if(!isset($labels[$token]))
				continue;
			
			// [TODO] This could be implemented
			if($type == 'context_url')
				continue;
			
			$label = $labels[$token];
			
			// Strip any modifiers
			if(false !== ($pos = strpos($token,'|')))
				$token = substr($token,0,$pos);
			
			$conditions[$token] = array('label' => $label, 'type' => $type);
		}
		
		foreach($labels as $token => $label) {
			if(false !== ($pos = strrpos($token, 'custom_'))) {
				$cfield_id = intval(substr($token, $pos + 7));
				
				if(null == ($cfield = @$custom_fields[$cfield_id]))
					continue;
				
				if(!isset($conditions[$token]))
					$conditions[$token] = array('label' => $label, 'type' => $cfield->type);
				
				// [TODO] Can we load these option a different way so this foreach isn't needed?
				switch($cfield->type) {
					case Model_CustomField::TYPE_DROPDOWN:
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						$conditions[$token]['options'] = @$cfield->params['options'];
						break;
				}
			}
		}
		
		return $conditions;
	}
	
	abstract function setEvent(?Model_DevblocksEvent $event_model=null, ?Model_TriggerEvent $trigger=null);
	
	/**
	 *
	 * @param Model_TriggerEvent $trigger
	 * @return Model_DevblocksEvent
	 */
	function generateSampleEventModel(Model_TriggerEvent $trigger) {
		$actions = null;
		
		return new Model_DevblocksEvent(
			static::ID,
			[
				'key' => 'value',
				'actions' => &$actions,
			]
		);
	}
	
	/**
	 * @internal
	 */
	function setLabels($labels) {
		natcasesort($labels);
		$this->_labels = $labels;
	}
	
	/**
	 * @internal
	 */
	function setValues($values) {
		$this->_values = $values;
		
		if(isset($values['_types']))
			$this->_setTypes($values['_types']);
	}
	
	/**
	 * @internal
	 */
	function getValues() {
		return $this->_values;
	}
	
	/**
	 * @internal
	 */
	function getLabels(?Model_TriggerEvent $trigger = null) {
		// Lazy load
		if(empty($this->_labels))
			$this->setEvent(null, $trigger);
		
		if(null != $trigger && !empty($trigger->variables)) {
			foreach($trigger->variables as $k => $var) {
				$this->_labels[$k] = '(variable) ' . $var['label'];
			}
		}
		
		// Sort
		asort($this->_labels);
		
		return $this->_labels;
	}
	
	/**
	 * @internal
	 */
	private function _setTypes($types) {
		$this->_types = $types;
	}
	
	/**
	 * @internal
	 */
	function getTypes() {
		if(!isset($this->_values['_types']))
			return [];
		
		return $this->_values['_types'];
	}
	
	/**
	 * @internal
	 */
	function getValuesContexts($trigger) {
		// Custom fields
		
		$cfields = [];
		$custom_fields = DAO_CustomField::getAll();
		
		// cfields
		$labels = $this->getLabels($trigger);
		
		if(is_array($labels))
			foreach($labels as $token => $label) {
				$matches = [];
				if(preg_match('#.*?_{0,1}custom_(\d+)$#', $token, $matches)) {
					@$cfield_id = $matches[1];
					
					if(empty($cfield_id))
						continue;
					
					if(!isset($custom_fields[$cfield_id]))
						continue;
					
					switch($custom_fields[$cfield_id]->type) {
						case Model_CustomField::TYPE_LINK:
							$link_context = $custom_fields[$cfield_id]->params['context'] ?? null;
							
							if(empty($link_context))
								break;
							
							$cfields[$token] = array(
								'label' => $label,
								'context' => $link_context,
							);
							
							// Include deep context links from this custom field link
							$link_labels = $link_values = [];
							CerberusContexts::getContext($link_context, null, $link_labels, $link_values, null, true);
							
							foreach($labels as $link_token => $link_label) {
								$link_matches = [];
								if(preg_match('#^'.$token.'_(.*?)__label$#', $link_token, $link_matches)) {
									@$link_key = $link_matches[1];
									
									if(empty($link_key))
										continue;
									
									if(isset($link_values[$link_key.'__context'])) {
										$cfields[$token . '_' . $link_key . '_id'] = array(
											'label' => $link_label,
											'context' => $link_values[$link_key.'__context'],
										);
									}
								}
							}
							
							break;
						
						case Model_CustomField::TYPE_WORKER:
							$cfields[$token] = array(
								'label' => $label,
								'context' => CerberusContexts::CONTEXT_WORKER,
							);
							break;
						
						default:
							if(null != ($cfield_ext = $custom_fields[$cfield_id]->getTypeExtension())) {
								$cfield_ext->getValuesContexts($custom_fields[$cfield_id], $token, $cfields);
							}
					}
				}
			}
		
		// Behavior Vars
		$vars = DevblocksEventHelper::getVarValueToContextMap($trigger);
		
		return array_merge($cfields, $vars);
	}
	
	function renderEventParams(?Model_TriggerEvent $trigger=null) {}
	
	/**
	 * @internal
	 */
	function getConditions($trigger, $sorted=true) {
		if(isset($this->_conditions_cache[$trigger->id])) {
			return $this->_conditions_cache[$trigger->id];
		}
		
		$conditions = array(
			'_calendar_availability' => array('label' => 'Calendar availability', 'type' => ''),
			'_custom_script' => array('label' => 'Custom script', 'type' => ''),
			'_day_of_week' => array('label' => 'Calendar day of week', 'type' => ''),
			'_day_of_month' => array('label' => 'Calendar day of month', 'type' => ''),
			'_month_of_year' => array('label' => 'Calendar month of year', 'type' => ''),
			'_time_of_day' => array('label' => 'Calendar time of day', 'type' => ''),
		);
		$custom = $this->getConditionExtensions($trigger);
		
		if(!empty($custom) && is_array($custom))
			$conditions = array_merge($conditions, $custom);
		
		// Trigger variables
		if(is_array($trigger->variables))
			foreach($trigger->variables as $key => $var) {
				$conditions[$key] = array(
					'label' => '(variable) ' . $var['label'],
					'type' => $var['type']
				);
				
				if($var['type'] == Model_CustomField::TYPE_DROPDOWN)
					$conditions[$key]['options'] = DevblocksPlatform::parseCrlfString($var['params']['options'] ?? '');
			}
		
		// Plugins
		// [TODO] This should filter by event type
		$manifests = Extension_DevblocksEventCondition::getAll(false);
		foreach($manifests as $manifest) {
			$conditions[$manifest->id] = array('label' => $manifest->params['label']);
		}
		
		if($sorted) {
			DevblocksPlatform::sortObjects($conditions, '[label]');
			$this->_conditions_cache[$trigger->id] = $conditions;
		}
		
		return $conditions;
	}
	
	/**
	 * @param Model_TriggerEvent $behavior
	 * @param $new_params
	 * @param string $error
	 * @return boolean
	 */
	function prepareEventParams(?Model_TriggerEvent $behavior, &$new_params, &$error) : bool {
		$error = null;
		return true;
	}
	
	abstract function getConditionExtensions(Model_TriggerEvent $trigger);
	abstract function renderConditionExtension($token, $as_token, $trigger, $params=[], $seq=null);
	abstract function runConditionExtension($token, $as_token, $trigger, $params, DevblocksDictionaryDelegate $dict);
	
	/**
	 * @internal
	 */
	function renderCondition($token, $trigger, $params=[], $seq=null) {
		$conditions = $this->getConditions($trigger, false);
		$condition_extensions = $this->getConditionExtensions($trigger);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix','condition'.$seq);
		
		switch($token) {
			case '_calendar_availability':
				// Get readable by VA
				$calendars = DAO_Calendar::getReadableByActor(array(CerberusContexts::CONTEXT_BOT, $trigger->bot_id));
				$tpl->assign('calendars', $calendars);
				
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_calendar_availability.tpl');
			
			case '_custom_script':
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_custom_script.tpl');
			
			case '_month_of_year':
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_month_of_year.tpl');
			
			case '_day_of_month':
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_number.tpl');
			
			case '_day_of_week':
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_day_of_week.tpl');
			
			case '_time_of_day':
				return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_time_of_day.tpl');
			
			default:
				if(null != (@$condition = $conditions[$token])) {
					// Automatic types
					switch(@$condition['type']) {
						case Model_CustomField::TYPE_CHECKBOX:
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_bool.tpl');
						case Model_CustomField::TYPE_DATE:
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_date.tpl');
						case Model_CustomField::TYPE_MULTI_LINE:
						case Model_CustomField::TYPE_SINGLE_LINE:
						case Model_CustomField::TYPE_URL:
						case 'phone':
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_string.tpl');
						case Model_CustomField::TYPE_LIST:
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_string_list.tpl');
						case Model_CustomField::TYPE_NUMBER:
							//case 'percent':
						case 'id':
						case 'size_bytes':
						case 'time_mins':
						case 'time_secs':
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_number.tpl');
						case Model_CustomField::TYPE_DROPDOWN:
						case Model_CustomField::TYPE_MULTI_CHECKBOX:
							$tpl->assign('condition', $condition);
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_dropdown.tpl');
						case Model_CustomField::TYPE_WORKER:
							$tpl->assign('workers', DAO_Worker::getAll());
							return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_worker.tpl');
						default:
							if(str_starts_with($condition['type'] ?? '', 'ctx_')) {
								return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/conditions/_number.tpl');
								
							} else {
								// Custom
								if(isset($condition_extensions[$token])) {
									return $this->renderConditionExtension($token, $token, $trigger, $params, $seq);
									
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
	
	/**
	 * @internal
	 */
	function runCondition($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$logger = DevblocksPlatform::services()->log('Bot');
		$conditions = $this->getConditions($trigger, false);
		
		// Cache the extensions
		if(!isset($this->_conditions_extensions_cache[$trigger->id])) {
			$this->_conditions_extensions_cache[$trigger->id] = $this->getConditionExtensions($trigger);
		}
		
		$extensions = @$this->_conditions_extensions_cache[$trigger->id] ?: [];
		
		$not = false;
		$pass = true;
		
		$now = time();
		
		// Overload the current time? (simulate)
		if(isset($dict->_current_time)) {
			$now = $dict->_current_time;
		}
		
		$logger->info('');
		$logger->info(sprintf("Checking condition `%s`...", $token));
		
		// Built-in conditions
		switch($token) {
			case '_calendar_availability':
				if(false == (@$calendar_id = $params['calendar_id']))
					return false;
				
				$is_available = $params['is_available'] ?? null;
				$from = $params['from'] ?? null;
				$to = $params['to'] ?? null;
				
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
				
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
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
				$param_value = $params['value'] ?? null;
				
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
				
				@$months = DevblocksPlatform::importVar($params['month'],'array',[]);
				
				switch($oper) {
					case 'is':
						$month = date('n', $now);
						$pass = in_array($month, $months);
						break;
				}
				break;
			case '_day_of_month':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$dom_expected = DevblocksPlatform::importVar($params['value'],'integer',0);
				$dom_actual = date('d', $now);
				
				switch($oper) {
					case 'is':
						$pass = $dom_expected == $dom_actual;
						break;
					case 'gt':
						$pass = $dom_actual > $dom_expected;
						break;
					case 'lt':
						$pass = $dom_actual < $dom_expected;
						break;
				}
				break;
			case '_day_of_week':
				$not = (substr($params['oper'],0,1) == '!');
				$oper = ltrim($params['oper'],'!');
				
				@$days = DevblocksPlatform::importVar($params['day'],'array',[]);
				
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
							$param_value = $params['value'] ?? null;
							
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
							break;
						
						case Model_CustomField::TYPE_LIST:
							$not = (substr($params['oper'],0,1) == '!');
							$oper = ltrim($params['oper'],'!');
							
							$tpl_builder = DevblocksPlatform::services()->templateBuilder();
							$param_value = $tpl_builder->build($params['value'] ?? '', $dict);
							
							$logger->info(sprintf("Text: `%s` %s%s `%s`",
								$value,
								(!empty($not) ? 'not ' : ''),
								$oper,
								$param_value
							));
							
							$token_parts = explode('_', $token);
							$field_id = array_pop($token_parts);
							$token_cfields = implode('_', $token_parts);
							$contains = false;
							
							switch($oper) {
								case 'contains':
									if(!isset($dict->$token_cfields)
										|| !is_array($dict->$token_cfields)
										|| !isset($dict->$token_cfields[$field_id])) {
										$contains = false;
										break;
									}
									
									foreach($dict->$token_cfields[$field_id] as $value) {
										if(!$contains && 0 == strcasecmp($param_value, $value)) {
											$contains = true;
											break;
										}
									}
									
									$pass = $contains;
									break;
							}
							break;
						
						case Model_CustomField::TYPE_NUMBER:
						case 'id':
						case 'time_mins':
						case 'time_secs':
							$not = (str_starts_with($params['oper'] ?? '', '!'));
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
							$not = (str_starts_with($params['oper'] ?? '', '!'));
							$oper = ltrim($params['oper'],'!');
							$desired_values = $params['values'] ?? [];
							
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
							
							$matches = [];
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
								// Is all of
								case 'is':
									$hits = array_intersect($value, $params['values']);
									$pass = (count($hits) == count($value));
									break;
								
								// Is any of
								case 'in':
									$hits = array_intersect($value, $params['values']);
									$pass = !empty($hits);
									break;
							}
							break;
						
						case Model_CustomField::TYPE_WORKER:
							$worker_ids = $params['worker_id'] ?? null;
							$not = (str_starts_with($params['oper'] ?? '', '!'));
							$oper = ltrim($params['oper'],'!');
							
							if(!is_array($value))
								$value = empty($value) ? [] : array($value);
							
							if(is_null($worker_ids))
								$worker_ids = [];
							
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
							if(str_starts_with($condition['type'] ?? '', 'ctx_')) {
								$count = (isset($dict->$token) && is_array($dict->$token)) ? count($dict->$token) : 0;
								
								$not = (str_starts_with($params['oper'] ?? '', '!'));
								$oper = ltrim($params['oper'],'!');
								$desired_count = intval($params['value'] ?? 0);
								
								$logger->info(sprintf("Count: %d %s%s %d",
									$count,
									(!empty($not) ? 'not ' : ''),
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
									$pass = $this->runConditionExtension($token, $token, $trigger, $params, $dict);
								} else {
									if(null != ($ext = DevblocksPlatform::getExtension($token, true))
										&& $ext instanceof Extension_DevblocksEventCondition) { /* @var $ext Extension_DevblocksEventCondition */
										$pass = $ext->run($token, $trigger, $params, $dict);
									}
								}
							}
							break;
					}
				} else {
					$logger->info("  ... FAIL (invalid condition)");
					return false;
				}
				break;
		}
		
		// Inverse operator?
		if($not)
			$pass = !$pass;
		
		$logger->info(sprintf("  ... %s", ($pass ? 'PASS' : 'FAIL')));
		
		return $pass;
	}
	
	/**
	 * @internal
	 */
	function getActions($trigger) { /* @var $trigger Model_TriggerEvent */
		$actions = [
			'_create_calendar_event' => [
				'label' => 'Create calendar event',
				'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
				'deprecated' => true,
				'params' => [
					'calendar_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The ID of the [calendar](/docs/records/types/calendar/) to add the event to',
					],
					'title' => [
						'type' => 'text',
						'required' => true,
						'notes' => 'The name of the event',
					],
					'when' => [
						'type' => 'timestamp',
						'required' => true,
						'notes' => 'The start datetime of the event',
					],
					'until' => [
						'type' => 'timestamp',
						'required' => true,
						'notes' => 'The end of datetime of the event',
					],
					'is_available' => [
						'type' => 'bit',
						'notes' => '`0`=busy, `1`=available',
					],
					'comment' => [
						'type' => 'text',
						'notes' => 'An optional comment to add to the new record',
					],
					'run_in_simulator' => [
						'type' => 'bit',
						'notes' => 'Create new records from the simulator: `0`=no, `1`=yes',
					],
					'object_var' => [
						'type' => 'text',
						'notes' => 'Save the new record into this `var_` behavior variable',
					],
				],
			],
			'_exit' => [
				'label' => 'Behavior exit',
				'params' => [
					'mode' => [
						'type' => 'text',
						'notes' => 'may be `suspend` on resumable behaviors, otherwise omit',
					],
				],
			],
			'_get_key' => [
				'label' => 'Get persistent key',
				'params' => [
					'key' => [
						'type' => 'string',
						'required' => true,
						'notes' => 'The key of the value to retrieve from storage',
					],
					'var' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'Save the returned value to this placeholder',
					],
				],
			],
			'_get_links' => [
				'label' => 'Get links',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'links_context' => [
						'type' => 'context',
						'required' => true,
						'notes' => 'Fetch links of [record type](/docs/records/types/)',
					],
					'var' => [
						'type' => 'placeholder',
						'notes' => 'Save the link results to this placeholder',
					],
					'behavior_var' => [
						'type' => 'placeholder',
						'notes' => 'Set this behavior variable with the link results',
					],
				],
			],
			'_get_worklist_metric' => [
				'label' => 'Get worklist metric',
				'deprecated' => true,
				'notes' => 'Use [Execute Data Query](/docs/bots/events/actions/core.bot.action.data_query/) instead.',
				'params' => [],
			],
			'_run_behavior' => [
				'label' => 'Behavior run',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'behavior_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The ID of the [behavior](/docs/records/types/behavior/) to execute',
					],
					'var_*' => [
						'type' => 'mixed',
						'notes' => 'Input variables for the target behavior',
					],
					'run_in_simulator' => [
						'type' => 'bit',
						'notes' => 'Run the target behavior in the simulator: `0`=no, `1`=yes',
					],
					'var' => [
						'type' => 'placeholder',
						'notes' => 'Save the behavior results to this placeholder',
					],
				],
			],
			'_run_subroutine' => [
				'label' => 'Behavior call subroutine',
				'params' => [
					'subroutine' => [
						'type' => 'text',
						'required' => true,
						'notes' => 'The name of the behavior [subroutine](/docs/bots/behaviors/#subroutines) to execute',
					],
				],
			],
			'_schedule_behavior' => [
				'label' => 'Behavior schedule',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'behavior_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The ID of the [behavior](/docs/records/types/behavior/) to execute',
					],
					'var_*' => [
						'type' => 'mixed',
						'notes' => 'Input variables for the target behavior',
					],
					'run_date' => [
						'type' => 'template',
						'notes' => 'When to run the scheduled behavior (e.g. `now`, `+2 days`, `Friday 8am`)',
					],
					'on_dupe' => [
						'type' => 'text',
						'notes' => '`first` (only schedule earliest), `last` (only schedule latest), or omit to allow multiple occurrences',
					],
				],
			],
			'_set_custom_var' => [
				'label' => 'Set custom placeholder',
				'params' => [
					'var' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder to set',
					],
					'value' => [
						'type' => 'string',
						'required' => true,
						'notes' => 'The new value of the placeholder',
					],
					'format' => [
						'type' => 'string',
						'notes' => 'The format of the value: `json`, or omit for text',
					],
					'is_simulator_only' => [
						'type' => 'bit',
						'notes' => 'Only set the placeholder in simulator mode: `0`=no, `1`=yes',
					],
				],
			],
			'_set_custom_var_snippet' => [
				'label' => 'Set snippet placeholder',
				'params' => [
					'var' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'Save the snippet output to this placeholder',
					],
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'snippet_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The ID of the [snippet](/docs/records/types/snippet/) to use',
					],
				],
			],
			'_set_key' => [
				'label' => 'Set persistent key',
				'params' => [
					'key' => [
						'type' => 'string',
						'required' => true,
						'notes' => 'The key to set in storage',
					],
					'value' => [
						'type' => 'string',
						'required' => true,
						'notes' => 'The value to set in storage',
					],
					'expires_at' => [
						'type' => 'datetime',
						'notes' => 'When to expire the key (e.g. `now`, `+2 days`, `Friday 8am`); omit to never expire',
					],
				],
			],
			'_unschedule_behavior' => [
				'label' => 'Behavior unschedule',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'behavior_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The ID of the [behavior](/docs/records/types/behavior/) to remove',
					],
				],
			],
			'add_watchers' => [
				'label' =>'Add watchers',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'worker_id' => [
						'type' => 'id[]',
						'required' => true,
						'notes' => 'An array of [worker](/docs/records/types/worker/) IDs to add as watchers to the target record',
					],
				],
			],
			'create_comment' => [
				'label' => 'Create comment',
				'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
				'deprecated' => true,
				'params' => [
				],
			],
			'create_notification' => [
				'label' => 'Create notification',
				'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
				'deprecated' => true,
				'params' => [
				],
			],
			'create_task' => [
				'label' => 'Create task',
				'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) instead.',
				'deprecated' => true,
				'params' => [
				],
			],
			'create_ticket' => [
				'label' => 'Create ticket',
				'notes' => 'Use [Record create](/docs/bots/events/actions/core.bot.action.record.create/) or [Execute email parser](/docs/bots/events/actions/core.bot.action.email_parser/) instead.',
				'deprecated' => true,
				'params' => [
				],
			],
			'send_email' => [
				'label' => 'Send email',
				'params' => [
					'from_address_id' => [
						'type' => 'id',
						'required' => true,
						'notes' => 'The sender [email address](/docs/records/types/address/) ID to as `From:`',
					],
					'send_as' => [
						'type' => 'text',
						'notes' => 'The personalized `From:` name',
					],
					'to' => [
						'type' => 'text',
						'required' => true,
						'notes' => 'A list of `To:` recipient email addresses delimited with commas',
					],
					'cc' => [
						'type' => 'text',
						'notes' => 'A list of `Cc:` recipient email addresses delimited with commas',
					],
					'bcc' => [
						'type' => 'text',
						'notes' => 'A list of `Bcc:` recipient email addresses delimited with commas',
					],
					'subject' => [
						'type' => 'text',
						'required' => true,
						'notes' => 'The `Subject:` of the email message',
					],
					'headers' => [
						'type' => 'text',
						'notes' => 'A list of `Header: Value` pairs delimited with newlines',
					],
					'format' => [
						'type' => 'text',
						'notes' => '`parsedown` for Markdown/HTML, or omitted for plaintext',
					],
					'content' => [
						'type' => 'text',
						'notes' => 'The email message body',
					],
					'html_template_id' => [
						'type' => 'id',
						'notes' => 'The [html template](/docs/records/types/html_template/) to use with Markdown format',
					],
					'bundle_ids' => [
						'type' => 'id[]',
						'notes' => 'An array of [file bundles](/docs/records/types/file_bundle/) to attach',
					],
					'run_in_simulator' => [
						'type' => 'bit',
						'notes' => 'Send live email in the simulator: `0`=no, `1`=yes',
					],
				],
			],
			'set_links' => [
				'label' => 'Set links',
				'params' => [
					'on' => [
						'type' => 'placeholder',
						'required' => true,
						'notes' => 'The placeholder/variable containing the target record',
					],
					'is_remove' => [
						'type' => 'bit',
						'notes' => '`0` (add links), `1` (remove links)',
					],
					'context_objects' => [
						'type' => 'array',
						'required' => true,
						'notes' => 'An array of `record_type:record_id` pairs to link to the target',
					],
				],
			],
		];
		
		$actions = array_map(
			function($action) {
				$action['scope'] = 'global';
				return $action;
			},
			$actions
		);
		
		$custom = $this->getActionExtensions($trigger);
		
		if(!empty($custom) && is_array($custom)) {
			$custom = array_map(function($action) {
				$action['scope'] = 'local';
				return $action;
			}, $custom);
			
			$actions = array_merge($actions, $custom);
		}
		
		// Trigger variables
		
		if(is_array($trigger->variables))
			foreach($trigger->variables as $key => $var) {
				$actions[$key] = [
					'label' => 'Set (variable) ' . $var['label'],
					'scope' => 'local',
				];
			}
		
		// Add plugin extensions
		
		$manifests = Extension_DevblocksEventAction::getAll(false, $trigger->event_point);
		
		// Filter extensions by VA permissions
		
		if(false != ($bot = $trigger->getBot())) {
			$manifests = $bot->filterActionManifestsByAllowed($manifests);
		}
		
		if(is_array($manifests))
			foreach($manifests as $manifest) {
				$action = [];
				
				if(method_exists($manifest->class, 'getMeta')) {
					$action = call_user_func([$manifest->class, 'getMeta']);
				}
				
				$action['label'] = $manifest->params['label'];
				$action['scope'] = 'global';
				
				$actions[$manifest->id] = $action;
			}
		
		// Sort by label
		
		DevblocksPlatform::sortObjects($actions, '[label]');
		
		return $actions;
	}
	
	abstract function getActionExtensions(Model_TriggerEvent $trigger);
	abstract function renderActionExtension($token, $trigger, $params=[], $seq=null);
	abstract function runActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict);
	protected function simulateActionExtension($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {}
	function renderSimulatorTarget($trigger, $event_model) {}
	
	/**
	 * @internal
	 */
	function renderAction($token, $trigger, $params=[], $seq=null) {
		$actions = $this->getActionExtensions($trigger);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('trigger', $trigger);
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix','action'.$seq);
		
		// Is this an event-provided action?
		if(null != ($actions[$token] ?? null)) {
			$this->renderActionExtension($token, $trigger, $params, $seq);
			
			// Nope, it's a global action
		} else {
			switch($token) {
				case '_create_calendar_event':
					DevblocksEventHelper::renderActionCreateCalendarEvent($trigger);
					break;
				
				case '_exit':
					return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_action_exit.tpl');
				
				case '_get_key':
					DevblocksEventHelper::renderActionGetKey($trigger);
					break;
				
				case '_get_links':
					DevblocksEventHelper::renderActionGetLinks($trigger);
					break;
				
				case '_get_worklist_metric':
					DevblocksEventHelper::renderActionGetWorklistMetric($trigger);
					break;
				
				case '_set_custom_var':
					$tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_set_custom_var.tpl');
					break;
				
				case '_set_custom_var_snippet':
					DevblocksEventHelper::renderActionSetPlaceholderUsingSnippet($trigger, $params);
					break;
				
				case '_set_key':
					DevblocksEventHelper::renderActionSetKey($trigger);
					break;
				
				case '_run_behavior':
					DevblocksEventHelper::renderActionRunBehavior($trigger);
					break;
				
				case '_schedule_behavior':
					$dates = [];
					$conditions = $this->getConditions($trigger, false);
					foreach($conditions as $key => $data) {
						if(isset($data['type']) && $data['type'] == Model_CustomField::TYPE_DATE)
							$dates[$key] = $data['label'];
					}
					$tpl->assign('dates', $dates);
					
					DevblocksEventHelper::renderActionScheduleBehavior($trigger);
					break;
				
				case '_run_subroutine':
					$subroutines = $trigger->getNodes('subroutine');
					$tpl->assign('subroutines', $subroutines);
					
					$tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_action_run_subroutine.tpl');
					break;
				
				case '_unschedule_behavior':
					DevblocksEventHelper::renderActionUnscheduleBehavior($trigger);
					break;
				
				case 'add_watchers':
					DevblocksEventHelper::renderActionAddWatchers($trigger);
					break;
				
				case 'create_comment':
					DevblocksEventHelper::renderActionCreateComment($trigger);
					break;
				
				case 'create_notification':
					DevblocksEventHelper::renderActionCreateNotification($trigger);
					break;
				
				case 'create_task':
					DevblocksEventHelper::renderActionCreateTask($trigger);
					break;
				
				case 'create_ticket':
					DevblocksEventHelper::renderActionCreateTicket($trigger);
					break;
				
				case 'send_email':
					$email_recipients = method_exists($this, 'getActionEmailRecipients') ? $this->getActionEmailRecipients() : null;
					DevblocksEventHelper::renderActionSendEmail($trigger, $email_recipients);
					break;
				
				case 'set_links':
					DevblocksEventHelper::renderActionSetLinks($trigger);
					break;
				
				default:
					// Variables
					if(DevblocksPlatform::strStartsWith($token, 'var_')) {
						@$var = $trigger->variables[$token];
						$var_type = $var['type'] ?? null;
						
						switch($var_type) {
							case Model_CustomField::TYPE_CHECKBOX:
								return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_set_bool.tpl');
								break;
							case Model_CustomField::TYPE_DATE:
								// Restricted to VA-readable calendars
								$calendars = DAO_Calendar::getReadableByActor(array(CerberusContexts::CONTEXT_BOT, $trigger->bot_id));
								$tpl->assign('calendars', $calendars);
								return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_set_date.tpl');
								break;
							case Model_CustomField::TYPE_NUMBER:
								return $tpl->display('devblocks:cerb.behaviors.legacy::internal/decisions/actions/_set_number.tpl');
								break;
							case Model_CustomField::TYPE_SINGLE_LINE:
								return DevblocksEventHelper::renderActionSetVariableString($this->getLabels($trigger));
								break;
							case Model_CustomField::TYPE_LINK:
								if(false == ($link_context_mft = Extension_DevblocksContext::get(@$var['params']['context'], false)))
									break;
								
								$aliases = Extension_DevblocksContext::getAliasesForContext($link_context_mft);
								
								return DevblocksEventHelper::renderActionSetVariableString(
									$this->getLabels($trigger),
									sprintf('(enter one %s record ID)', $aliases['singular'])
								);
								break;
							case Model_CustomField::TYPE_DROPDOWN:
								return DevblocksEventHelper::renderActionSetVariablePicklist($token, $trigger, $params);
								break;
							case Model_CustomField::TYPE_WORKER:
								return DevblocksEventHelper::renderActionSetVariableWorker($token, $trigger, $params);
								break;
							case 'contexts':
								return DevblocksEventHelper::renderActionSetListAbstractVariable($token, $trigger, $params);
								break;
							default:
								if(DevblocksPlatform::strStartsWith($var_type, 'ctx_')) {
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
	
	/**
	 * Are we doing a dry run?
	 *
	 * @internal
	 */
	function simulateAction($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$actions = $this->getActionExtensions($trigger);
		
		if(null != ($actions[$token] ?? null)) {
			if(method_exists($this, 'simulateActionExtension'))
				return $this->simulateActionExtension($token, $trigger, $params, $dict);
			
		} else {
			switch($token) {
				case '_create_calendar_event':
					return DevblocksEventHelper::simulateActionCreateCalendarEvent($params, $dict);
				
				case '_exit':
					@$mode = (isset($params['mode']) && $params['mode'] == 'suspend') ? 'suspend' : 'stop';
					
					return sprintf(">>> %s the behavior\n",
						($mode == 'suspend' ? 'Suspending' : 'Exiting')
					);
				
				case '_get_key':
					return DevblocksEventHelper::simulateActionGetKey($params, $dict);
				
				case '_get_links':
					return DevblocksEventHelper::simulateActionGetLinks($params, $dict);
				
				case '_get_worklist_metric':
					return DevblocksEventHelper::simulateActionGetWorklistMetric($params, $dict);
				
				case '_set_custom_var':
					$var = $params['var'] ?? null;
					$format = $params['format'] ?? null;
					
					$value = ($format == 'json') ? @DevblocksPlatform::strFormatJson(json_encode($dict->$var, true)) : $dict->$var;
					
					return sprintf(">>> Setting custom placeholder {{%s}}:\n%s\n\n",
						$var,
						$value
					);
				
				case '_set_custom_var_snippet':
					$var = $params['var'] ?? null;
					
					$value = $dict->$var;
					
					return sprintf(">>> Setting custom placeholder {{%s}}:\n%s\n\n",
						$var,
						$value
					);
				
				case '_set_key':
					return DevblocksEventHelper::simulateActionSetKey($params, $dict);
				
				case '_run_behavior':
					return DevblocksEventHelper::simulateActionRunBehavior($params, $dict);
				
				case '_schedule_behavior':
					return DevblocksEventHelper::simulateActionScheduleBehavior($params, $dict);
				
				case '_run_subroutine':
					$subroutine_node = null;
					
					foreach($trigger->getNodes('subroutine') as $node) {
						if($node->title == $params['subroutine']) {
							$subroutine_node = $node;
							break;
						}
					}
					
					if(!$subroutine_node)
						return;
					
					return sprintf(">>> Running subroutine: %s (#%d)\n",
						$subroutine_node->title,
						$subroutine_node->id
					);
				
				case '_unschedule_behavior':
					return DevblocksEventHelper::simulateActionUnscheduleBehavior($params, $dict);
				
				case 'add_watchers':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					return DevblocksEventHelper::simulateActionAddWatchers($params, $dict, $on_default);
				
				case 'create_comment':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					return DevblocksEventHelper::simulateActionCreateComment($params, $dict, $on_default);
				
				case 'create_notification':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					return DevblocksEventHelper::simulateActionCreateNotification($params, $dict, $on_default);
				
				case 'create_task':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					return DevblocksEventHelper::simulateActionCreateTask($params, $dict, $on_default);
				
				case 'create_ticket':
					return DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
				
				case 'send_email':
					return DevblocksEventHelper::simulateActionSendEmail($params, $dict);
				
				case 'set_links':
					return DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
				
				default:
					// Variables
					if(str_starts_with($token, 'var_')) {
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
	
	/**
	 * @internal
	 */
	function runAction($token, $trigger, $params, DevblocksDictionaryDelegate $dict, $dry_run=false) {
		$actions = $this->getActionExtensions($trigger);
		
		$out = '';
		
		if(null != ($actions[$token] ?? null)) {
			// Is this a dry run?  If so, don't actually change anything
			if($dry_run) {
				$out = $this->simulateAction($token, $trigger, $params, $dict);
			} else {
				$this->runActionExtension($token, $trigger, $params, $dict);
			}
			
		} else {
			switch($token) {
				case '_create_calendar_event':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionCreateCalendarEvent($params, $dict);
					
					break;
				
				case '_exit':
					@$mode = (isset($params['mode']) && $params['mode'] == 'suspend') ? 'suspend' : 'stop';
					$dict->__exit = $mode;
					
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					break;
				
				case '_get_key':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionGetKey($params, $dict);
					break;
				
				case '_get_links':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionGetLinks($params, $dict);
					break;
				
				case '_get_worklist_metric':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionGetWorklistMetric($params, $dict);
					break;
				
				case '_set_custom_var':
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					
					$var = $params['var'] ?? null;
					$value = $params['value'] ?? null;
					$format = $params['format'] ?? null;
					$is_simulator_only = (bool)($params['is_simulator_only'] ?? null);
					
					// If this variable is only set in the simulator, and we're not simulating, abort
					if($is_simulator_only && !$dry_run)
						return;
					
					if(!empty($var) && !empty($value)) {
						$value = $tpl_builder->build($value, $dict);
						$dict->$var = ($format == 'json') ? @json_decode($value, true) : $value;
					}
					
					if($dry_run) {
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					} else {
						return;
					}
					break;
				
				case '_set_custom_var_snippet':
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					$cache = DevblocksPlatform::services()->cache();
					
					$on = $params['on'] ?? null;
					$snippet_id = $params['snippet_id'] ?? null;
					$var = $params['var'] ?? null;
					$placeholder_values = $params['placeholders'] ?? null;
					
					if(empty($on) || empty($var) || empty($snippet_id))
						return;
					
					// Cache the snippet in the request (multiple runs of the VA; parser, etc)
					$cache_key = sprintf('snippet_%d', $snippet_id);
					if(false == ($snippet = $cache->load($cache_key, false, true))) {
						if(false == ($snippet = DAO_Snippet::get($snippet_id)))
							return;
						
						$cache->save($snippet, $cache_key, [], 0, true);
					}
					
					if(empty($var))
						return;
					
					$values_to_contexts = $this->getValuesContexts($trigger);
					
					@$on_context = $values_to_contexts[$on];
					
					if(empty($on) || !is_array($on_context))
						return;
					
					$snippet_labels = [];
					$snippet_values = [];
					
					// Load snippet target dictionary
					if(!empty($snippet->context) && $snippet->context == $on_context['context']) {
						CerberusContexts::getContext($on_context['context'], $dict->$on, $snippet_labels, $snippet_values, '', false, false);
					}
					
					// Prompts
					
					// [TODO] If a required prompted placeholder is missing, abort
					
					$prompts = $snippet->getPrompts();
					
					if(is_array($prompts) && is_array($placeholder_values))
						foreach($prompts as $prompt) {
							$prompt_name = $prompt['name'];
							if(!isset($placeholder_values[$prompt_name])) {
								$snippet_values[$prompt_name] = $prompt['default'];
								
							} else {
								// Convert placeholders
								$snippet_values[$prompt_name] = $tpl_builder->build($placeholder_values[$prompt_name], $dict);
							}
						}
					
					$value = $tpl_builder->build($snippet->content, $snippet_values);
					$dict->set($var, $value);
					
					if($dry_run) {
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					} else {
						return;
					}
					break;
				
				case '_set_key':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionSetKey($params, $dict);
					break;
				
				case '_run_behavior':
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionRunBehavior($params, $dict);
					break;
				
				case '_run_subroutine':
					$subroutine_node = null;
					
					foreach($trigger->getNodes('subroutine') as $node) {
						if($node->title == $params['subroutine']) {
							$subroutine_node = $node;
							break;
						}
					}
					
					if(false == $subroutine_node)
						break;
					
					$dict->__goto = $subroutine_node->id;
					
					if($dry_run)
						$out = $this->simulateAction($token, $trigger, $params, $dict);
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
				
				case 'add_watchers':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionAddWatchers($params, $dict, $on_default);
					else
						DevblocksEventHelper::runActionAddWatchers($params, $dict, $on_default);
					break;
				
				case 'create_comment':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionCreateComment($params, $dict, $on_default);
					else
						DevblocksEventHelper::runActionCreateComment($params, $dict, $on_default);
					break;
				
				case 'create_notification':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionCreateNotification($params, $dict, $on_default);
					else
						DevblocksEventHelper::runActionCreateNotification($params, $dict, $on_default);
					break;
				
				case 'create_task':
					$on_default = method_exists($this, 'getActionDefaultOn') ? $this->getActionDefaultOn() : null;
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionCreateTask($params, $dict, $on_default);
					else
						DevblocksEventHelper::runActionCreateTask($params, $dict, $on_default);
					break;
				
				case 'create_ticket':
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionCreateTicket($params, $dict);
					else
						DevblocksEventHelper::runActionCreateTicket($params, $dict);
					break;
				
				case 'send_email':
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionSendEmail($params, $dict);
					else
						DevblocksEventHelper::runActionSendEmail($params, $dict);
					break;
				
				case 'set_links':
					if($dry_run)
						$out = DevblocksEventHelper::simulateActionSetLinks($trigger, $params, $dict);
					else
						DevblocksEventHelper::runActionSetLinks($trigger, $params, $dict);
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
			
			if(!isset($dict->__simulator_output) || !is_array($dict->__simulator_output))
				$dict->__simulator_output = [];
			
			$node_id = array_pop($log);
			
			if(!empty($node_id) && false !== ($node = DAO_DecisionNode::get($node_id))) {
				if(array_key_exists($token, $all_actions)) {
					$output = array(
						'action' => $node->title,
						'title' => $all_actions[$token]['label'],
						'content' => $out,
					);
					
					$previous_output = $dict->__simulator_output;
					$previous_output[] = $output;
					$dict->__simulator_output = $previous_output;
					unset($out);
				}
			}
		}
	}
};

abstract class Extension_DevblocksEventCondition extends DevblocksExtension {
	/**
	 * @internal
	 */
	public static function getAll($as_instances=false, $for_event=null) {
		$extensions = DevblocksPlatform::getExtensions('devblocks.event.condition', false);
		$results = [];
		
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
	
	abstract function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null);
	abstract function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict);
};