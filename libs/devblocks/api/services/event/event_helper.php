<?php
class DevblocksEventHelper {
	public static function getVarValueToContextMap($trigger) { /* @var $trigger Model_TriggerEvent */
		$values_to_contexts = array();
		
		// Bot
		
		$va = $trigger->getBot();
		
		$values_to_contexts['_trigger_va_id'] = array(
			'label' => '(Self) ' . $va->name,
			'context' => CerberusContexts::CONTEXT_BOT,
			'context_id' => $va->id,
		);
		
		// Behavior variables
		
		if(is_array($trigger->variables))
		foreach($trigger->variables as $var_key => $var) {
			if(substr($var_key,0,4) == 'var_') {
				if(substr($var['type'],0,4) == 'ctx_') {
					$ctx_id = substr($var['type'],4);
					$values_to_contexts[$var_key] = array(
						'label' => '(variable) ' . $var['label'],
						'context' => $ctx_id,
					);
				}
			}
		}
		
		return $values_to_contexts;
	}
	
	public static function getContextToMacroMap() {
		$exts_event = Extension_DevblocksEvent::getAll(false);
		$context_to_macros = array();
		
		foreach($exts_event as $ext_event_id => $ext_event) {
			if(!isset($ext_event->params['macro_context']))
				continue;
			
			$context_to_macros[$ext_event->params['macro_context']] = $ext_event_id;
		}
		
		return $context_to_macros;
	}
	
	private static function _getRelativeDateUsingCalendar($calendar_id, $rel_date) {
		$today = strtotime('today', time());
		$cache = DevblocksPlatform::getCacheService();
		
		if(empty($calendar_id) || false == ($calendar = DAO_Calendar::get($calendar_id))) {
			// Fallback to plain 24-hour time
			$value = strtotime($rel_date);
			
		} else {
			/*
			 * [TODO] We should probably cache this, but we need an efficient way to invalidate
			 * even when the datasource is a worklist, or multiple contexts.
			 */
			$calendar_events = $calendar->getEvents($today, strtotime('+2 weeks 23:59:59', $today));
			$availability = $calendar->computeAvailability($today, strtotime('+2 weeks 23:59:59', $today), $calendar_events);
			
			// [TODO] Do we have enough available time to schedule this?
			// 	We should be able to lazy append events + availability as we go
			
			$value = $availability->scheduleInRelativeTime(time(), $rel_date);
		}
		
		return $value;
	}
	
	public static function renderSimulatorTarget($context, $context_id, $trigger, $event_model) {
		if(false == ($context_ext = Extension_DevblocksContext::get($context)))
			return;
		
		$labels = array();
		$values = array();
		CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('context', $context);
		$tpl->assign('context_id', $context_id);
		$tpl->assign('context_ext', $context_ext);
		$tpl->assign('dict', DevblocksDictionaryDelegate::instance($values));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/simulator/target.tpl');
	}
	
	/*
	 * Action: Custom Fields
	 */
	
	public static function getCustomFieldValuesFromParams($params) {
		$custom_fields = DAO_CustomField::getAll();
		$custom_field_values = array();
		
		if(is_array($params))
		foreach($params as $key => $val) {
			if(substr($key,0,6) == 'field_') {
				$cf_id = substr($key, 6);
				
				if(!isset($custom_fields[$cf_id]))
					continue;
				
				switch($custom_fields[$cf_id]->type) {
					case Model_CustomField::TYPE_MULTI_CHECKBOX:
						$custom_field_values[$cf_id] = array_combine($val, $val);
						break;
						
					default:
						$custom_field_values[$cf_id] = $val;
						break;
				}
			}
		}

		return $custom_field_values;
	}

	public static function getCustomFieldsetsFromParams($params) {
		$custom_fieldsets = DAO_CustomFieldset::getAll();
		$custom_fields = DAO_CustomField::getAll();
		$results = array();
		
		if(is_array($params))
		foreach($params as $key => $val) {
			if(substr($key,0,6) == 'field_') {
				$cf_id = substr($key, 6);
				
				if(!isset($custom_fields[$cf_id]))
					continue;
				
				@$custom_fieldset_id = $custom_fields[$cf_id]->custom_fieldset_id;
				
				if($custom_fieldset_id && isset($custom_fieldsets[$custom_fieldset_id]))
					$results[$custom_fieldset_id] = $custom_fieldsets[$custom_fieldset_id];
			}
		}

		return $results;
	}
	
	static function getActionCustomFieldsFromLabels($labels) {
		$actions = array();
		$custom_fields = DAO_CustomField::getAll();
		
		// Set custom fields
		foreach($labels as $key => $label) {
			if(preg_match('#(.*?)_custom_([0-9]+)#', $key, $matches)) {
				if(!isset($matches[2]) || !isset($custom_fields[$matches[2]]))
					continue;
				
				$field = $custom_fields[$matches[2]];
				
				// [TODO] Block nested cfields (from links) in 6.7.6, fully imp in 6.8?
				if($field->type == Model_CustomField::TYPE_LINK)
					continue;
				
				$actions[sprintf("set_cf_%s", $key)] = array(
					'label' => 'Set ' . mb_convert_case($label, MB_CASE_LOWER),
					'type' => $field->type,
				);
			}
		}
		
		return $actions;
	}
	
	static function renderActionSetCustomField(Model_CustomField $custom_field, $trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_URL:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
				break;
				
			case Model_CustomField::TYPE_CHECKBOX:
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_bool.tpl');
				break;
				
			case Model_CustomField::TYPE_DATE:
				// Restricted to VA-readable calendars
				$calendars = DAO_Calendar::getReadableByActor(array(CerberusContexts::CONTEXT_BOT, $trigger->bot_id));
				$tpl->assign('calendars', $calendars);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_date.tpl');
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				$tpl->assign('options', @$custom_field->params['options']);
				$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_dropdown.tpl');
				$tpl->clearAssign('options');
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				$tpl->assign('options', @$custom_field->params['options']);
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
	
	static function simulateActionSetAbstractField($field_name, $field_type, $value_key, $params, DevblocksDictionaryDelegate $dict) {
		$field_types = Model_CustomField::getTypes();
		
		if(empty($field_type) || !isset($field_types[$field_type]))
			return;
		
		$out = '';
		
		$out .= sprintf(">>> Setting %s to:\n",
			$field_name
		);
		
		switch($field_type) {
			case Model_CustomField::TYPE_CHECKBOX:
				@$value = $params['value'];
				$out .= sprintf("%s\n",
					!empty($value) ? 'yes' : 'no'
				);
				
				if(!empty($value_key)) {
					$dict->$value_key = $value;
				}
				break;
				
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_URL:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);
				
				$out .= sprintf("%s\n",
					$value
				);
				 
				if(!empty($value_key)) {
					$dict->$value_key = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $dict);
				
				if(!is_numeric($value))
					$value = intval(@strtotime($value));
				
				if(!empty($value)) {
					$out .= sprintf("%s (%s)\n",
						date('D M d Y h:ia', $value),
						$value
					);
				}
				
				if(!empty($value_key)) {
					$dict->$value_key = $value;
				}
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$opts = $params['values'];

				$out .= sprintf("%s\n",
					implode(', ', $opts)
				);
				
				if(!empty($value_key)) {
					$dict->$value_key = implode(',',$opts);
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_id = $params['worker_id'];
				
				// Variable?
				if(substr($worker_id,0,4) == 'var_') {
					@$worker_id = intval($dict->$worker_id);
				}
				
				if(empty($worker_id)) {
					$out .= "nobody\n";
					
				} else {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= sprintf("%s\n",
							$worker->getName()
						);
					}
				}
				
				if(!empty($value_key)) {
					$dict->$value_key = $worker_id;
				}
				break;
				
			default:
				//self::runActionExtension($token, $trigger, $params, $dict);
				//self::simulateActionExtension($token, $trigger, $params, $dict);
				break;
		}
		
		return $out;
	}
	
	static function simulateActionSetCustomField($token, $params, DevblocksDictionaryDelegate $dict) {
		if(!preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches))
			return;
		
		$custom_key = $matches[1];
		$field_id = $matches[2];
		
		if(null == ($custom_field = DAO_CustomField::get($field_id)))
			return;

		$context = $custom_field->context;
		$custom_key_id = $custom_key . '_id';
		$context_id = $dict->$custom_key_id;

		if(empty($field_id) || empty($context) || empty($context_id))
			return;
		
		$out = '';
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_CHECKBOX:
				@$value = $params['value'];
				
				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);
				
				$out .= sprintf("%s\n",
					!empty($value) ? 'yes' : 'no'
				);
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_URL:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);

				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);
				
				$out .= sprintf("%s\n",
					$value
				);
				 
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);
				
				if(!isset($custom_field->params['options']) || !is_array($custom_field->params['options'])) {
					$out .= "[ERROR] The picklist custom field has no options. Ignoring.";
					break;
				}
				
				$possible_values = array_map('strtolower', $custom_field->params['options']);
				
				if(false !== ($value_idx = array_search(DevblocksPlatform::strLower($value), $possible_values))) {
					$value = $custom_field->params['options'][$value_idx];
				} else {
					$out .= sprintf("[ERROR] The given value (%s) doesn't exist in the picklist. Ignoring.", $value);
					break;
				}
				
				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);
				
				$out .= sprintf("%s\n",
					$value
				);
				 
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				@$mode = $params['mode'];
				
				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);
				
				switch($mode) {
					case 'calendar':
						@$calendar_id = $params['calendar_id'];
						@$rel_date = $params['calendar_reldate'];

						$value = DevblocksEventHelper::_getRelativeDateUsingCalendar($calendar_id, $rel_date);
						
						if(false !== $value)
							$dict->$token = $value;
						
						break;
						
					default:
						if(!isset($params['value']))
							return;
						
						$tpl_builder = DevblocksPlatform::getTemplateBuilder();
						$value = $tpl_builder->build($params['value'], $dict);
						break;
				}
				
				$value = is_numeric($value) ? $value : @strtotime($value);

				if(!empty($value)) {
					$out .= sprintf("%s (%s)\n",
						date('D M d Y h:ia', $value),
						$value
					);
				}
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$opts = $params['values'];
				
				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);

				$out .= sprintf("%s\n",
					implode(', ', $opts)
				);
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = implode(',',$opts);

					$array =& $dict->$value_key;
					$array[$field_id] = $opts;
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_id = $params['worker_id'];
				
				$out .= sprintf(">>> Setting %s to:\n",
					$custom_field->name
				);
				
				// Variable?
				if(substr($worker_id,0,4) == 'var_') {
					@$worker_id = intval($dict->$worker_id);
				}
				
				if(empty($worker_id)) {
					$out .= "nobody\n";
					
				} else {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= sprintf("%s\n",
							$worker->getName()
						);
					}
				}
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $worker_id;

					$array =& $dict->$value_key;
					$array[$field_id] = $worker_id;
				}
				break;
				
			default:
				//$this->runActionExtension($token, $trigger, $params, $dict);
				//$this->simulateActionExtension($token, $trigger, $params, $dict);
				break;
		}
		
		return $out;
	}
	
	static function runActionSetCustomField($token, $params, DevblocksDictionaryDelegate $dict) {
		if(!preg_match('#set_cf_(.*?)_custom_([0-9]+)#', $token, $matches))
			return;
		
		$custom_key = $matches[1];
		$field_id = $matches[2];
		
		if(null == ($custom_field = DAO_CustomField::get($field_id)))
			return;
		
		$context = $custom_field->context;
		$custom_key_id = $custom_key . '_id';
		$context_id = $dict->$custom_key_id;
		
		if(empty($field_id) || empty($context) || empty($context_id))
			return;
		
		/**
		 * If we have a fieldset-based custom field that doesn't exist in scope yet
		 * then link it.
		 */
		if($custom_field->custom_fieldset_id && !isset($dict->$token)) {
			DAO_ContextLink::setLink($context, $context_id, CerberusContexts::CONTEXT_CUSTOM_FIELDSET, $custom_field->custom_fieldset_id);
		}
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_CHECKBOX:
			case Model_CustomField::TYPE_NUMBER:
			case Model_CustomField::TYPE_URL:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);

				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;

					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
				
			case Model_CustomField::TYPE_DROPDOWN:
				@$value = $params['value'];
				
				$builder = DevblocksPlatform::getTemplateBuilder();
				$value = $builder->build($value, $dict);
				
				$possible_values = array_map('strtolower', $custom_field->params['options']);
				
				if(false === (DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value)))
					break;

				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;

					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				@$mode = $params['mode'];
				
				switch($mode) {
					case 'calendar':
						@$calendar_id = $params['calendar_id'];
						@$rel_date = $params['calendar_reldate'];

						$value = DevblocksEventHelper::_getRelativeDateUsingCalendar($calendar_id, $rel_date);
						
						break;
						
					default:
						if(!isset($params['value']))
							return;
						
						$tpl_builder = DevblocksPlatform::getTemplateBuilder();
						$value = $tpl_builder->build($params['value'], $dict);
						break;
				}

				$value = is_numeric($value) ? $value : @strtotime($value);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);
						
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				
				break;
				
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				@$opts = $params['values'];
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $opts, true);

				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = implode(',',$opts);

					$array =& $dict->$value_key;
					$array[$field_id] = $opts;
				}
				
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_id = $params['worker_id'];

				// Variable?
				if(substr($worker_id,0,4) == 'var_') {
					@$worker_id = intval($dict->$worker_id);
				}
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $worker_id);
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $worker_id;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $worker_id;
				}
				break;
				
			default:
				self::runActionExtension($token, $trigger, $params, $dict);
				break;
		}
	}
	
	static function renderActionCreateRecordSetCustomFields($context, &$tpl) {
		$custom_fields = DAO_CustomField::getByContext($context, false);
		$tpl->assign('custom_fields', $custom_fields);

		if(false != ($params = $tpl->getVariable('params'))) {
			$params = $params->value;

			$custom_field_values = DevblocksEventHelper::getCustomFieldValuesFromParams($params);
			$tpl->assign('custom_field_values', $custom_field_values);
			
			$custom_fieldsets_linked = DevblocksEventHelper::getCustomFieldsetsFromParams($params);
			$tpl->assign('custom_fieldsets_linked', $custom_fieldsets_linked);
		}
	}
	
	static function simulateActionCreateRecordSetCustomFields($params, $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		$custom_field_values = DevblocksEventHelper::getCustomFieldValuesFromParams($params);
		
		$out = '';
		
		if(is_array($custom_field_values))
		foreach($custom_field_values as $cf_id => $val) {
			if(!isset($custom_fields[$cf_id]))
				continue;
			
			if(is_null($val))
				continue;
			
			if(is_array($val))
				$val = implode('; ', $val);
			
			switch($custom_fields[$cf_id]->type) {
				case Model_CustomField::TYPE_WORKER:
					if(!empty($val) && !is_numeric($val)) {
						if(isset($dict->$val)) {
							$val = $dict->$val;
							
							// If it's an array, pick a random key
							if(is_array($val)) {
								$key = array_rand($val, 1);
								
								if(is_numeric($key)) {
									$val = $key;
									
								} else {
									$val = array_shift($val);
									
									if($val instanceof DevblocksDictionaryDelegate) {
										@$val = intval($val->id);
									}
								}
							}
							
						}
					}
					
					if(isset($workers[$val])) {
						$set_worker = $workers[$val];
						$val = $set_worker->getName();
					}
					break;
					
				default:
					$val = $tpl_builder->build($val, $dict);
					break;
			}
			
			$out .= $custom_fields[$cf_id]->name . ': ' . $val . "\n";
		}
		
		return $out;
	}
	
	static function runActionCreateRecordSetCustomFields($context, $context_id, $params, &$dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		if(empty($context) || empty($context_id))
			return false;
		
		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		$custom_field_values = DevblocksEventHelper::getCustomFieldValuesFromParams($params);
		
		$vals = array();
		
		if(is_array($custom_field_values))
		foreach($custom_field_values as $cf_id => $val) {
			switch($custom_fields[$cf_id]->type) {
				case Model_CustomField::TYPE_WORKER:
					if(!empty($val) && !is_numeric($val)) {
						if(isset($dict->$val)) {
							$val = $dict->$val;
						}
					}
					break;
						
				default:
					if(is_string($val))
						$val = $tpl_builder->build($val, $dict);
					break;
			}
		
			$vals[$cf_id] = $val;
		}
		
		if(!empty($vals))
			DAO_CustomFieldValue::formatAndSetFieldValues($context, $context_id, $vals);
	}
	
	static function simulateActionCreateRecordSetLinks($params, $dict) {
		@$link_to = DevblocksPlatform::importVar($params['link_to'],'array',array());
		$out = '';
		
		if(!empty($link_to)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($link_to, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= ">>> Linking new record to:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
			}
			
			$out .= "\n";
		}
		
		return $out;
	}
	
	static function runActionCreateRecordSetLinks($context, $context_id, $params, &$dict) {
		@$link_to = DevblocksPlatform::importVar($params['link_to'],'array',array());
		
		if(!empty($link_to)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($link_to, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					DAO_ContextLink::setLink($context, $context_id, $on_object->_context, $on_object->id);
				}
			}
		}
		
		return true;
	}
	
	static function simulateActionCreateRecordSetVariable($params, $dict) {
		@$trigger = $dict->__trigger;
		@$object_var = $params['object_var'];
		$out = '';
		
		if($object_var && $trigger && isset($trigger->variables[$object_var])) {
			$out .= sprintf(">>> Adding new object to variable: {{%s}}\n",
				$object_var
			);
		}
		
		return $out;
	}
	
	static function runActionCreateRecordSetVariable($context, $context_id, $params, &$dict) {
		@$trigger = $dict->__trigger;
		@$object_var = $params['object_var'];
		
		if($object_var && $trigger && isset($trigger->variables[$object_var])) {
			CerberusContexts::getContext($context, $context_id, $labels, $values, null, true, true);
			
			if(!isset($dict->$object_var))
				$dict->$object_var = array();
			
			$ptr =& $dict->$object_var;
			
			$ptr[$context_id] = new DevblocksDictionaryDelegate($values);
		}
	}
	
	// Dates
	
	static function runActionSetDate($token, $params, DevblocksDictionaryDelegate $dict) {
		@$mode = $params['mode'];
				
		switch($mode) {
			case 'calendar':
				@$calendar_id = $params['calendar_id'];
				@$rel_date = $params['calendar_reldate'];

				$value = DevblocksEventHelper::_getRelativeDateUsingCalendar($calendar_id, $rel_date);
				
				if(false !== $value)
					$dict->$token = $value;
				
				break;
				
			default:
				if(!isset($params['value']))
					return;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $dict);
				
				$value = is_numeric($value) ? $value : @strtotime($value);
				$dict->$token = $value;
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
	
	static function renderActionSetVariablePicklist($token, $trigger, $params) {
		$tpl = DevblocksPlatform::getTemplateService();
		//$tpl->assign('token_labels', $labels);
		
		if(isset($trigger->variables[$token])) {
			@$options = $trigger->variables[$token]['params']['options'];
			
			if(isset($options) && !empty($options))
				$tpl->assign('options', DevblocksPlatform::parseCrlfString($options));
		}
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_picklist.tpl');
	}
	
	static function renderActionSetVariableWorker($token, $trigger, $params) {
		$tpl = DevblocksPlatform::getTemplateService();

		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Groups
		$tpl->assign('groups', DAO_Group::getAll());

		// Variables
		$worker_variables = array();
		if(is_array($trigger->variables))
		foreach($trigger->variables as $var_key => $var) {
			if($var['type'] == 'ctx_' . CerberusContexts::CONTEXT_WORKER)
				$worker_variables[$var_key] = $var['label'];
		}
		$tpl->assign('worker_variables', $worker_variables);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_worker.tpl');
	}
	
	static function renderActionSetListVariable($token, $trigger, $params, $context) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		if(null == ($view = DevblocksEventHelper::getViewFromAbstractJson($token, $params, $trigger, $context)))
			return;
		
		$view->persist();
		
		$tpl->assign('context', $context);
		$tpl->assign('params', $params);
		$tpl->assign('view', $view);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_list.tpl');
	}
	
	static function simulateActionSetVariable($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$var = $trigger->variables[$token];
		
		if(empty($var) || !is_array($var))
			return;

		@$var_type = $var['type'];
	 
		if(substr($var_type,0,4) == 'ctx_') {
			@$objects = $dict->$token;
			
			if(empty($objects)) {
				return sprintf(">>> Setting empty list %s\n",
					$token
				);
			}
			
			$context_extid = substr($var_type,4);
			//$context_ext = Extension_DevblocksContext::get($context_extid);
			$context_ext = DevblocksPlatform::getExtension($context_extid, false);
			
			$out = sprintf(">>> Putting %d objects in %s list '%s':\n",
				count($objects),
				DevblocksPlatform::strLower($context_ext->name),
				$token
			);
			
			$fields = array();
			$null = array();
			CerberusContexts::getContext($context_extid, null, $fields, $null, null, true);

			$out .= "\n";
			
			$counter = 0;
			foreach($objects as $object) {
				@$label = $object->_label;
				
				$out .= sprintf(" [%d] %s\n",
					++$counter,
					$label ? $label : '(object)'
				);
			}

			$obj_name = DevblocksPlatform::strToPermalink(DevblocksPlatform::strLower($context_ext->name),'_');
			
			$out .= "\nTo use the list as placeholders:\n";
			
			$out .= sprintf("{%% for %s in %s %%}\n * {{%s._label}}\n{%% endfor %%}\n",
				$obj_name,
				$token,
				$obj_name
			);
			
			$out .= "\nPlaceholders:\n";

			foreach($fields as $k => $v) {
				if(substr($k,0,1)=='_')
					continue;
				$out .= sprintf(" * {{%s.%s}}\n",
					$obj_name,
					$k
				);
				
				if(false !== stristr($k, 'custom_')) {
					$out .= sprintf("     %s\n",
						mb_convert_case($v, MB_CASE_TITLE)
					);
				}
			}
			
		} else {
			@$value = is_array($dict->$token) ? implode(',', $dict->$token) : $dict->$token;
			
			switch($var_type) {
				case Model_CustomField::TYPE_DATE:
					$value = sprintf("%s (%s)\n",
						@date('D M d Y h:ia', $value),
						$value
					);
					break;
					
				case Model_CustomField::TYPE_WORKER:
					$workers = DAO_Worker::getAll();
					
					if(isset($workers[$value]))
						$value = $workers[$value]->getName();
					break;
			}
			
			$out = sprintf(">>> Setting '%s' to:\n%s",
				$token,
				$value
			);
		}
		
		return $out;
	}
	
	static function runActionSetVariable($token, $trigger, $params, DevblocksDictionaryDelegate $dict) {
		@$var = $trigger->variables[$token];
		
		if(empty($var) || !is_array($var))
			return;
		
		switch($var['type']) {
			case Model_CustomField::TYPE_CHECKBOX:
				$value = (isset($params['value']) && !empty($params['value'])) ? true : false;
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_DATE:
				@$mode = $params['mode'];
				
				switch($mode) {
					case 'calendar':
						@$calendar_id = $params['calendar_id'];
						@$rel_date = $params['calendar_reldate'];
						
						$value = DevblocksEventHelper::_getRelativeDateUsingCalendar($calendar_id, $rel_date);
						
						if(false !== $value)
							$dict->$token = $value;
						
						break;
						
					default:
						if(!isset($params['value']))
							return;
						
						$tpl_builder = DevblocksPlatform::getTemplateBuilder();
						$value = $tpl_builder->build($params['value'], $dict);
						
						$value = is_numeric($value) ? $value : @strtotime($value);
						$dict->$token = $value;
						break;
				}
				
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$value = intval($params['value']);
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
			case Model_CustomField::TYPE_DROPDOWN:
			case Model_CustomField::TYPE_URL:
				if(!isset($params['value']))
					break;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $dict);
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_ids = $params['worker_id'];
				@$group_ids = $params['group_id'];
				@$variables = $params['vars'];
				@$mode = $params['mode'];
				@$opt_is_available = $params['opt_is_available'];
				@$opt_logged_in = $params['opt_logged_in'];
				
				$possible_workers = array();
				
				// Add workers
				if(!empty($worker_ids)) {
					foreach($worker_ids as $id)
						$possible_workers[$id] = true;
				}
				
				// Add groups
				if(!empty($group_ids)) {
					foreach($group_ids as $group_id)
					$members = DAO_Group::getGroupMembers($group_id);
					foreach($members as $member) {
						$possible_workers[$member->id] = true;
					}
				}

				// Add Worker variables
				if(is_array($variables)) {
					foreach($variables as $var_key) {
						if(isset($dict->$var_key) && is_array($dict->$var_key)) {
							foreach($dict->$var_key as $worker_id => $worker_context) {
								$possible_workers[$worker_id] = true;
							}
						}
					}
				}
				
				//
				
				$workers = DAO_Worker::getAll();
				
				// Filter: Logged in
				if(!empty($opt_logged_in)) {
					$workers_online = DAO_Worker::getAllOnline();
				}
				
				foreach(array_keys($possible_workers) as $k) {
					// Remove non-existent workers
					if(!isset($workers[$k])) {
						unset($possible_workers[$k]);
						continue;
					}
					
					$worker = $workers[$k];
		
					// Filter to online workers
					if(!empty($opt_logged_in) && !isset($workers_online[$k])) {
						unset($possible_workers[$k]);
						continue;
					}
					
					if(!empty($opt_is_available)) {
						@$availability_calendar_id = $worker->calendar_id;
					
						if(empty($availability_calendar_id)) {
							unset($possible_workers[$k]);
							continue;
							
						} else {
							if(false == ($calendar = DAO_Calendar::get($availability_calendar_id))) {
								unset($possible_workers[$k]);
								continue;
							}
							
							$from = '-5 mins';
							$to = '+5 mins';
							
							@$cal_from = strtotime("today", strtotime($from));
							@$cal_to = strtotime("tomorrow", strtotime($to));
							
							$calendar_events = $calendar->getEvents($cal_from, $cal_to);
							$availability = $calendar->computeAvailability($cal_from, $cal_to, $calendar_events);
							
							$pass = $availability->isAvailableBetween(strtotime($from), strtotime($to));
							
							// If the worker is not available, remove them from the list
							if(!$pass) {
								unset($possible_workers[$k]);
								continue;
							}
						}
					}
				}
		
				// We require at least one worker
				if(empty($possible_workers)) {
					$var_key = $var['key'];
					$dict->$var_key = 0;
					return;
				}
				
				$chosen_worker_id = 0;
				
				// Mode
				switch($mode) {
					// Random
					default:
					case 'random':
						$chosen_worker_id = array_rand($possible_workers, 1);
						break;
						
					// Sequential
					case 'seq':
						$log = EventListener_Triggers::getNodeLog();
						$node_id = end($log);

						$registry = DevblocksPlatform::getRegistryService();

						$key = sprintf("trigger.%d.action.%d.counter", $trigger->id, $node_id);
						
						$count = intval($registry->get($key));
						
						$registry->increment($key, 1);
						
						$idx = $count % count($possible_workers);
						
						$ids = array_keys($possible_workers);
						$chosen_worker_id = $ids[$idx];
						break;
						
					// Fewest open assignments
					case 'load_balance':
						$worker_loads = array();
						
						// Initialize
						foreach(array_keys($possible_workers) as $id) {
							$worker_loads[$id] = 0;
						}
						
						// Consult database
						$db = DevblocksPlatform::getDatabaseService();
						$sql = sprintf("SELECT COUNT(id) AS hits, owner_id FROM ticket WHERE status_id = 0 AND owner_id != 0 AND owner_id IN (%s) GROUP BY owner_id",
							implode(',', array_keys($possible_workers))
						);
						$results = $db->GetArraySlave($sql);
						
						if(!empty($results))
						foreach($results as $row) {
							$worker_loads[$row['owner_id']] = intval($row['hits']);
						}
						
						// Find the lowest load value
						$lowest_load = min($worker_loads);
						
						// Only keep workers with the lowest load
						$worker_loads = array_filter($worker_loads, function($e) use ($lowest_load) {
							if($e == $lowest_load)
								return true;
							
							return false;
						});
						
						// Pick a random worker if multiple have the same lowest load
						$chosen_worker_id = array_rand($worker_loads, 1);
						
						break;
				}
				
				$dict->$token = $chosen_worker_id;
				break;
				
			default:
				@$var_type = $var['type'];
			 
				if(substr($var_type,0,4) == 'ctx_') {
					$list_context = substr($var_type,4);
					DevblocksEventHelper::runActionSetListVariable($token, $list_context, $params, $dict);
				}
				break;
		}
	}
	
	// Set Links
	
	static function renderActionSetLinks($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_set_links.tpl');
	}
	
	static function simulateActionSetLinks($trigger, $params, DevblocksDictionaryDelegate $dict) {
		$to_contexts = array();
		
		$is_remove = (isset($params['is_remove']) && !empty($params['is_remove'])) ? true : false;
		
		$out = sprintf(">>> %s links:\n",
			((!$is_remove) ? 'Adding' : 'Removing')
		);

		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		
		if(isset($params['context_objects']) && is_array($params['context_objects']))
		foreach($params['context_objects'] as $to_context_string) {
			if(isset($dict->$to_context_string)) {
				$on = DevblocksEventHelper::onContexts($to_context_string, $values_to_contexts, $dict, false);

				if(is_array($on))
				foreach($on as $to_context_string)
					$to_contexts[] = $to_context_string;
				
			} elseif(substr($to_context_string,0,4) == 'var_') {
				if(!isset($trigger->variables[$to_context_string]))
					continue;
				
				$var = $trigger->variables[$to_context_string];
				
				if(!isset($var['type']))
					continue;
				
				$to_context = substr($var['type'], 4);
				
				if(is_array($dict->$to_context_string))
				foreach(array_keys($dict->$to_context_string) as $to_context_id) {
					$to_contexts[] = sprintf("%s:%d", $to_context, $to_context_id);
				}
				
			} else {
				$to_contexts[] = $to_context_string;
			}
		}
		
		if(is_array($to_contexts))
		foreach($to_contexts as $to_context_string) {
			@list($to_context, $to_context_id) = explode(':', $to_context_string);
			
			if(empty($to_context) || empty($to_context_id))
				continue;
			
			$to_context_ext = Extension_DevblocksContext::get($to_context);
			
			if(false === ($meta = $to_context_ext->getMeta($to_context_id)))
				continue;
			
			if(empty($meta['name']))
				continue;
			
			$out .= sprintf(" * %s (%s)\n", $meta['name'], $to_context_ext->manifest->name);
		}
		
		$out .= "\n";
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'], 'string', null);

		$on_result = DevblocksEventHelper::onContexts($on, $values_to_contexts, $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			$out .= ">>> On:\n";
			
			foreach($on_objects as $on_object) {
				$on_object_context = Extension_DevblocksContext::get($on_object->_context);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object->_label . "\n";
			}
			$out .= "\n";
		}
		
		return $out;
	}
	
	static function runActionSetLinks($trigger, $params, DevblocksDictionaryDelegate $dict) {
		$to_contexts = array();
		
		$is_remove = (isset($params['is_remove']) && !empty($params['is_remove'])) ? true : false;
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		
		if(isset($params['context_objects']) && is_array($params['context_objects']))
		foreach($params['context_objects'] as $to_context_string) {
			if(isset($dict->$to_context_string)) {
				$on = DevblocksEventHelper::onContexts($to_context_string, $values_to_contexts, $dict, false);

				if(is_array($on))
				foreach($on as $to_context_string)
					$to_contexts[] = $to_context_string;
				
			} elseif(substr($to_context_string,0,4) == 'var_') {
				if(!isset($trigger->variables[$to_context_string]))
					continue;
				
				$var = $trigger->variables[$to_context_string];
				
				if(!isset($var['type']))
					continue;
				
				$to_context = substr($var['type'], 4);
				
				if(is_array($dict->$to_context_string))
				foreach(array_keys($dict->$to_context_string) as $to_context_id) {
					$to_contexts[] = sprintf("%s:%d", $to_context, $to_context_id);
				}
				
			} else {
				$to_contexts[] = $to_context_string;
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'], 'string', null);

		$on_result = DevblocksEventHelper::onContexts($on, $values_to_contexts, $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				$from_context = $on_object->_context;
				$from_context_id = $on_object->id;
				
				if(is_array($to_contexts))
				foreach($to_contexts as $to_context_string) {
					@list($to_context, $to_context_id) = explode(':', $to_context_string);
					
					if(empty($to_context) || empty($to_context_id))
						continue;
					
					if($from_context == $to_context && $from_context_id == $to_context_id)
						continue;
					
					if(!$is_remove)
						DAO_ContextLink::setLink($from_context, $from_context_id, $to_context, $to_context_id);
					else
						DAO_ContextLink::deleteLink($from_context, $from_context_id, $to_context, $to_context_id);
				}
			}
		}
	}
	
	/*
	 * Action: Set a custom placeholder using snippet
	 */
	
	static function renderActionSetPlaceholderUsingSnippet($trigger, $params) { /* @var $trigger Model_TriggerEvent */
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger', $trigger);

		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$context_exts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_exts', $context_exts);
		
		if(false != (@$snippet_id = $params['snippet_id'])) {
			$tpl->assign('snippet', DAO_Snippet::get($snippet_id));
		}
		
		$tpl->display('devblocks:cerberusweb.core::events/action_set_placeholder_using_snippet.tpl');
	}
	
	/*
	 * Action: Get links
	 */
	
	static function renderActionGetLinks($trigger) { /* @var $trigger Model_TriggerEvent */
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('trigger', $trigger);

		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$context_exts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('context_exts', $context_exts);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_get_links.tpl');
	}
	
	static function simulateActionGetLinks($params, DevblocksDictionaryDelegate $dict) {
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		@$links_context = DevblocksPlatform::importVar($params['links_context'],'string','');
		@$var = DevblocksPlatform::importVar($params['var'],'string','');
		@$behavior_var = DevblocksPlatform::importVar($params['behavior_var'],'string','');
		
		$out = '';
		
		$trigger = $dict->__trigger;

		if(false == ($context_ext = Extension_DevblocksContext::get($links_context)))
			return;
		
		$out .= sprintf(">> Get %s links on:\n",
			$context_ext->manifest->name
		);
		
		// On
		
		if(!empty($on)) {
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					if(!isset($on_object->id) && empty($on_object->id))
						continue;

					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= '  * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
			}
			
			$out .= "\n";
		}
		
		// Placeholder
		
		if(!empty($var)) {
			$out .= sprintf(">>> Save links to placeholder named:\n  {{%s}}\n",
				$var
			);
		}
		
		// Save to variable
		
		if(!empty($behavior_var)) {
			$out .= sprintf(">>> Save links to variable:\n  %s\n",
				$behavior_var
			);
		}
		
		// Run it in the simulator too
		
		self::runActionGetLinks($params, $dict);
		
		return $out;
	}
	
	static function runActionGetLinks($params, $dict) {
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		@$links_context = DevblocksPlatform::importVar($params['links_context'],'string','');
		@$var = DevblocksPlatform::importVar($params['var'],'string','');
		@$behavior_var = DevblocksPlatform::importVar($params['behavior_var'],'string','');
		
		if(false == ($trigger = $dict->__trigger))
			return;

		if(false == ($context_ext = Extension_DevblocksContext::get($links_context)))
			return;
		
		if(!empty($on)) {
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];

			if(!empty($on_objects)) {
				$first = current($on_objects);
				$context = $first->_context;
				unset($first);
				
				$keys = array_map(function($e) {
					list($context, $context_id) = explode(':', $e);
					return $context_id;
				}, array_keys($on_objects));
				
				if(!empty($keys)) {
					$results = DAO_ContextLink::getContextLinks($context, $keys, $links_context);
					$data = array();
					
					if(is_array($results))
					foreach($results as $links) {
						foreach($links as $link_pair) {
							$values = array(
								'_context' => $link_pair->context,
								'id' => $link_pair->context_id,
							);

							$data[$link_pair->context_id] = DevblocksDictionaryDelegate::instance($values);
						}
					}
					
					if(!empty($var))
						$dict->$var = $data;
					
					if(!empty($behavior_var)) {
						if(!isset($dict->$behavior_var) || !is_array($dict->$behavior_var))
							$dict->$behavior_var = array();
						
						$ptr =& $dict->$behavior_var;
						
						if(is_array($data))
						foreach($data as $key => $val)
							$ptr[$key] = $val;
					}
				}
				
			}
		}
	}
	
	/*
	 * Action: Run Behavior
	 */
	
	static function renderActionRunBehavior($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Macros
		
		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		$context_to_macros = DevblocksEventHelper::getContextToMacroMap();
		$tpl->assign('context_to_macros', $context_to_macros);
		$tpl->assign('events_to_contexts', array_flip($context_to_macros));

		// Template
		
		$tpl->display('devblocks:cerberusweb.core::events/action_run_behavior.tpl');
	}
	
	static function simulateActionRunBehavior($params, DevblocksDictionaryDelegate $dict) {
		@$behavior_id = $params['behavior_id'];
		@$var = $params['var'];
		@$run_in_simulator = $params['run_in_simulator'];

		$trigger = $dict->__trigger;
		
		if(empty($behavior_id)) {
			return "[ERROR] No behavior is selected. Skipping...";
		}
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		if(null == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return "[ERROR] Behavior does not exist. Skipping...";
		
		if(null == ($ext = DevblocksPlatform::getExtension($behavior->event_point, true))) /* @var $ext Extension_DevblocksEvent */
			return "[ERROR] Behavior event does not exist. Skipping...";
		
		$out = sprintf(">>> Running behavior: %s\n",
			$behavior->title
		);
		
		// Variables as parameters
		
		$vars = array();
		
		if(is_array($params))
		foreach($params as $k => $v) {
			if(substr($k, 0, 4) == 'var_') {
				if(!isset($behavior->variables[$k]))
					continue;
				
				try {
					if(is_string($v))
						$v = $tpl_builder->build($v, $dict);

					$v = $behavior->formatVariable($behavior->variables[$k], $v);
					
					$vars[$k] = $v;
					
				} catch(Exception $e) {
					
				}
			}
		}
		
		if(is_array($vars) && !empty($vars)) {
			foreach($vars as $k => $v) {
				
				if(is_array($v)) {
					$vals = array();
					foreach($v as $kk => $vv)
						if(isset($vv->_label))
							$vals[] = $vv->_label;
					$v = implode("\n  ", $vals);
				}
				
				$out .= sprintf("\n* %s:%s\n",
					$behavior->variables[$k]['label'],
					!empty($v) ? (sprintf("\n   %s", $v)) : ('')
				);
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				if($run_in_simulator) {
					$out .= "\n";
				} else {
					$out .= "\n>>> On:\n";
				}
				
				foreach($on_objects as $on_object) {
					if(!isset($on_object->id) && empty($on_object->id))
						continue;

					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					
					if($run_in_simulator) {
						$out .= '=== On: (' . $on_object_context->manifest->name . ') ' . $on_object->_label . " ===\n";
					} else {
						$out .= '  * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
					}
					
					if($run_in_simulator) {
						// Save the current state so we can resume it after the remote behavior
						$log = EventListener_Triggers::getNodeLog();
						
						$runners = call_user_func(array($ext->manifest->class, 'trigger'), $behavior->id, $on_object->id, $vars);
						
						// Restore the current state
						EventListener_Triggers::setNodeLog($log);
						
						// Capture results
						
						if(isset($runners[$behavior->id])) {
							$new_dict = $runners[$behavior->id]; /* @var $new_dict DevblocksDictionaryDelegate */
							$dict->$var = $new_dict;
						}
						
						// [TODO] We could show this dictionary in the simulator
						
						// Merge simulator output

						if(isset($on_object->__simulator_output) && is_array($on_object->__simulator_output))
						foreach($on_object->__simulator_output as $simulator_entry) {
							$out .= sprintf("\n%s",
								str_replace('>>>', '>>>>', $simulator_entry['content'])
							);
						}
					}
				}
			}
		}
		
		$out .= sprintf("\n>>> Saving output to {{%s}}\n",
			$var
		);
		
		return $out;
	}
	
	static function runActionRunBehavior($params, $dict) {
		@$behavior_id = $params['behavior_id'];
		@$var = $params['var'];
		
		if(empty($behavior_id))
			return FALSE;
		
		if(false == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return FALSE;
		
		// Load event manifest
		if(false == ($ext = DevblocksPlatform::getExtension($behavior->event_point, false))) /* @var $ext DevblocksExtensionManifest */
			return FALSE;
		
		if(empty($var))
			return FALSE;
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		// Variables as parameters
		
		$vars = array();
		
		if(is_array($params))
		foreach($params as $k => $v) {
			if(substr($k, 0, 4) == 'var_') {
				if(!isset($behavior->variables[$k]))
					continue;
				
				try {
					if(is_string($v))
						$v = $tpl_builder->build($v, $dict);

					$v = $behavior->formatVariable($behavior->variables[$k], $v);
					
					$vars[$k] = $v;
					
				} catch(Exception $e) {
					
				}
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					if(!isset($on_object->id) && empty($on_object->id))
						continue;

					$log = EventListener_Triggers::getNodeLog();
					$runners = call_user_func(array($ext->class, 'trigger'), $behavior->id, $on_object->id, $vars);
					EventListener_Triggers::setNodeLog($log);
					
					if(null != (@$runner = $runners[$behavior->id])) {
						$dict->$var = $runner;
					}
					
				}
			}
		}

		return;
	}
	
	/*
	 * Action: Schedule Behavior
	 */
	
	static function renderActionScheduleBehavior($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Macros
		
		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		$context_to_macros = DevblocksEventHelper::getContextToMacroMap();
		$tpl->assign('context_to_macros', $context_to_macros);
		$tpl->assign('events_to_contexts', array_flip($context_to_macros));

		// Macros
		
		if(false == ($va = $trigger->getBot()))
			return;
		
		$macros = array();
		
		$results = DAO_TriggerEvent::getReadableByActor($va, null, true);
		
		foreach($results as $k => $macro) {
			if(!in_array($macro->event_point, $context_to_macros)) {
				continue;
			}

			if(false == ($macro_va = $macro->getBot())) {
				continue;
			}
			
			$macro->title = sprintf("[%s] %s%s",
				$macro_va->name,
				$macro->title,
				($macro->is_disabled ? ' (disabled)' : '')
			);
			
			$macros[$k] = $macro;
		}
		
		DevblocksPlatform::sortObjects($macros, 'title');
		
		$tpl->assign('macros', $macros);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::events/action_schedule_behavior.tpl');
	}
	
	static function simulateActionScheduleBehavior($params, DevblocksDictionaryDelegate $dict) {
		@$behavior_id = $params['behavior_id'];
		@$run_date = $params['run_date'];
		@$on_dupe = $params['on_dupe'];

		$trigger = $dict->__trigger;
		
		if(empty($behavior_id)) {
			return "[ERROR] No behavior is selected. Skipping...";
		}
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$run_date = $tpl_builder->build($run_date, $dict);
		
		@$run_timestamp = strtotime($run_date);
		
		if(null == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return "[ERROR] Behavior does not exist. Skipping...";
		
		$out = sprintf(">>> Scheduling behavior\n".
			"Behavior: %s\n".
			"When: %s (%s)\n",
			$behavior->title,
			date('Y-m-d h:ia', $run_timestamp),
			$run_date
		);
		
		switch($on_dupe) {
			case 'first':
				$out .= "Dupes: Only earliest\n";
				break;
			case 'last':
				$out .= "Dupes: Only latest\n";
				break;
			default:
				$out .= "Dupes: Allow multiple\n";
				break;
		}
		
		// Variables as parameters
		
		$vars = array();
		
		if(is_array($params))
		foreach($params as $k => $v) {
			if(substr($k, 0, 4) == 'var_') {
				if(!isset($behavior->variables[$k]))
					continue;
				
				try {
					if(is_string($v))
						$v = $tpl_builder->build($v, $dict);
					
					$v = $behavior->formatVariable($behavior->variables[$k], $v);
					
					$vars[$k] = $v;
					
				} catch(Exception $e) {
					
				}
			}
		}
		
		if(is_array($vars) && !empty($vars)) {
			foreach($vars as $k => $v) {
				
				if(is_array($v)) {
					$vals = array();
					foreach($v as $kk => $vv)
						if(isset($vv->_label))
							$vals[] = $vv->_label;
					$v = implode("\n  ", $vals);
				}
				
				$out .= sprintf("\n* %s:\n   %s\n",
					$behavior->variables[$k]['label'],
					$v
				);
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
				$out .= "\n";
			}
		}
		
		return $out;
	}
	
	static function runActionScheduleBehavior($params, $dict) {
		@$behavior_id = $params['behavior_id'];
		@$run_date = $params['run_date'];
		@$on_dupe = $params['on_dupe'];
		
		if(empty($behavior_id))
			return FALSE;
		
		if(null == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return FALSE;
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$run_date = $tpl_builder->build($run_date, $dict);
		
		@$run_timestamp = strtotime($run_date);
		
		// Variables as parameters
		
		$vars = array();
		
		if(is_array($params))
		foreach($params as $k => $v) {
			if(substr($k, 0, 4) == 'var_') {
				if(!isset($behavior->variables[$k]))
					continue;
				
				try {
					if(is_string($v))
						$v = $tpl_builder->build($v, $dict);
					
					$v = $behavior->formatVariable($behavior->variables[$k], $v);
					
					$vars[$k] = $v;
					
				} catch(Exception $e) {
					
				}
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					if(!isset($on_object->id) && empty($on_object->id))
						continue;
					
					switch($on_dupe) {
						// Only keep first
						case 'first':
							// Keep the first, delete everything else, and don't add a new one
							$behaviors = DAO_ContextScheduledBehavior::getByContext($on_object->_context, $on_object->id);
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
							DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id, $on_object->_context, $on_object->id);
							break;
						
						// Allow dupes
						default:
							// Do nothing
							break;
					}
					
					
					$fields = array(
						DAO_ContextScheduledBehavior::CONTEXT => $on_object->_context,
						DAO_ContextScheduledBehavior::CONTEXT_ID => $on_object->id,
						DAO_ContextScheduledBehavior::BEHAVIOR_ID => $behavior_id,
						DAO_ContextScheduledBehavior::RUN_DATE => intval($run_timestamp),
						DAO_ContextScheduledBehavior::VARIABLES_JSON => json_encode($vars),
					);
					DAO_ContextScheduledBehavior::create($fields);
				}
			}
		}

		return;
	}
	
	/*
	 * Action: Unschedule Behavior
	 */
	
	static function renderActionUnscheduleBehavior($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Macros
		
		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		$context_to_macros = DevblocksEventHelper::getContextToMacroMap();
		$tpl->assign('context_to_macros', $context_to_macros);
		$tpl->assign('events_to_contexts', array_flip($context_to_macros));

		// Macros
		
		if(false == ($va = $trigger->getBot()))
			return;
		
		$macros = array();
		
		$results = DAO_TriggerEvent::getReadableByActor($va, null, true);
		
		foreach($results as $k => $macro) {
			if(!in_array($macro->event_point, $context_to_macros)) {
				continue;
			}

			if(false == ($macro_va = $macro->getBot())) {
				continue;
			}
			
			$macro->title = sprintf("[%s] %s%s",
				$macro_va->name,
				$macro->title,
				($macro->is_disabled ? ' (disabled)' : '')
			);
			
			$macros[$k] = $macro;
		}
		
		DevblocksPlatform::sortObjects($macros, 'title');
		
		$tpl->assign('macros', $macros);
		
		$tpl->display('devblocks:cerberusweb.core::events/action_unschedule_behavior.tpl');
	}
	
	static function simulateActionUnscheduleBehavior($params, DevblocksDictionaryDelegate $dict) {
		@$behavior_id = $params['behavior_id'];

		if(empty($behavior_id) || null == ($behavior = DAO_TriggerEvent::get($behavior_id))) {
			return "[ERROR] No behavior is selected. Skipping...";
		}
		
		$out = sprintf(">>> Unscheduling behavior\n".
			"Behavior: %s\n",
			$behavior->title
		);

		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
				$out .= "\n";
			}
		}
		
		return $out;
	}
	
	static function runActionUnscheduleBehavior($params, DevblocksDictionaryDelegate $dict) {
		@$behavior_id = $params['behavior_id'];
		
		if(empty($behavior_id))
			return FALSE;
		
			// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$trigger = $dict->__trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id, $on_object->_context, $on_object->id);
				}
			}
		}
	}
	
	/*
	 * Action: Create Calendar Event
	 */
	
	static function renderActionCreateCalendarEvent($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$event = $trigger->getEvent();

		$calendars = DAO_Calendar::getWriteableByActor(array(CerberusContexts::CONTEXT_BOT, $trigger->bot_id));
		
		if(is_array($calendars))
		foreach($calendars as $calendar_id => $calendar) {
			if(isset($calendar->params['manual_disabled']) && !empty($calendar->params['manual_disabled']))
				unset($calendars[$calendar_id]);
		}
		
		$tpl->assign('calendars', $calendars);

		// [TODO] Including calendar variables

		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_CALENDAR_EVENT, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_calendar_event.tpl');
	}
	
	static function simulateActionCreateCalendarEvent($params, DevblocksDictionaryDelegate $dict) {
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		$calendars = array();
		
		@$calendar_key = DevblocksPlatform::importVar($params['calendar_id'], 'string', null);
		
		if(is_numeric($calendar_key)) {
			if(false != ($calendar = DAO_Calendar::get($calendar_key)))
				$calendars[$calendar->id] = $calendar;
			
		} elseif('var_' == substr($calendar_key,0,4)) {
			$calendar_ids = array_keys($dict->$calendar_key);
			
			if(is_array($calendar_ids))
				$calendars = CerberusContexts::getModels(CerberusContexts::CONTEXT_CALENDAR, $calendar_ids);
		}

		$calendars = array_filter($calendars, function($calendar) use ($trigger) {
			if(@!empty($calendar->params['manual_disabled']))
				return false;
			
			if(!Context_Calendar::isWriteableByActor($calendar, [CerberusContexts::CONTEXT_BOT, $trigger->bot_id]))
				return false;
			
			return true;
		});
		
		if(empty($calendars))
			return;
		
		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$title = $tpl_builder->build($params['title'], $dict);
		@$when = $tpl_builder->build($params['when'], $dict);
		@$until = $tpl_builder->build($params['until'], $dict);
		@$is_available = $tpl_builder->build($params['is_available'], $dict);
		
		if(!is_numeric($when))
			$when = intval(@strtotime($when));
		
		if(!is_numeric($until))
			$until = intval(@strtotime($until, $when));
		
		$comment = $tpl_builder->build($params['comment'], $dict);

		$out = sprintf(">>> Creating calendar event\n".
			"Title: %s\n".
			"When: %s (%s)\n".
			"Until: %s (%s)\n".
			"Status: %s\n".
			"",
			$title,
			(!empty($when) ? date("Y-m-d h:ia", $when) : 'none'),
			$params['when'],
			(!empty($until) ? date("Y-m-d h:ia", $until) : 'none'),
			$params['until'],
			(!empty($params['is_available']) ? 'Available' : 'Busy')
		);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";

		if(is_array($calendars)) {
			$out .= ">>> On:\n";
			
			foreach($calendars as $calendar) {
				$out .= ' * ' . $calendar->name . "\n";
			}
			$out .= "\n";
		}
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			$out .= ">>> Adding watchers to calendar event:\n";
			foreach($watcher_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= ' * ' . $worker->getName() . "\n";
				}
			}
			$out .= "\n";
		}
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on calendar event\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			self::runActionCreateCalendarEvent($params, $dict);
		}
		
		return $out;
	}
	
	static function runActionCreateCalendarEvent($params, DevblocksDictionaryDelegate $dict) {
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();

		$calendars = array();
		
		@$calendar_key = DevblocksPlatform::importVar($params['calendar_id'], 'string', null);
		
		if(is_numeric($calendar_key)) {
			if(false != ($calendar = DAO_Calendar::get($calendar_key)))
				$calendars[$calendar->id] = $calendar;
			
		} elseif('var_' == substr($calendar_key,0,4)) {
			$calendar_ids = array_keys($dict->$calendar_key);
			
			if(is_array($calendar_ids))
				$calendars = CerberusContexts::getModels(CerberusContexts::CONTEXT_CALENDAR, $calendar_ids);
		}

		$calendars = array_filter($calendars, function($calendar) use ($trigger) {
			if(@!empty($calendar->params['manual_disabled']))
				return false;
			
			if(!Context_Calendar::isWriteableByActor($calendar, [CerberusContexts::CONTEXT_BOT, $trigger->bot_id]))
				return false;
			
			return true;
		});
		
		if(empty($calendars))
			return;
		
		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		@$title = $tpl_builder->build($params['title'], $dict);
		@$when = $tpl_builder->build($params['when'], $dict);
		@$until = $tpl_builder->build($params['until'], $dict);
		@$is_available = $tpl_builder->build($params['is_available'], $dict);
		
		if(!is_numeric($when))
			$when = intval(@strtotime($when));
		
		if(!is_numeric($until))
			$until = intval(@strtotime($until, $when));
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(is_array($calendars))
		foreach($calendars as $calendar_id => $calendar) {
			if(!($calendar instanceof Model_Calendar))
				continue;
			
			$fields = array(
				DAO_CalendarEvent::NAME => $title,
				DAO_CalendarEvent::DATE_START => $when,
				DAO_CalendarEvent::DATE_END => $until,
				DAO_CalendarEvent::CALENDAR_ID => $calendar_id,
				DAO_CalendarEvent::IS_AVAILABLE => !empty($is_available) ? 1 : 0,
			);
			
			if(false == ($calendar_event_id = DAO_CalendarEvent::create($fields)))
				return false;
			
			// Custom fields
			DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_CALENDAR_EVENT, $calendar_event_id, $params, $dict);
				
			// Watchers
			if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
				CerberusContexts::addWatchers(CerberusContexts::CONTEXT_CALENDAR_EVENT, $calendar_event_id, $watcher_worker_ids);
			}
				
			// Comment content
			if(!empty($comment)) {
				$fields = array(
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
					DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
					DAO_Comment::COMMENT => $comment,
					DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_CALENDAR_EVENT,
					DAO_Comment::CONTEXT_ID => $calendar_event_id,
					DAO_Comment::CREATED => time(),
				);
				DAO_Comment::create($fields, $notify_worker_ids);
			}
			
			// Set object variable
			DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_CALENDAR_EVENT, $calendar_event_id, $params, $dict);
		}
			
		return true;
	}
	
	/*
	 * Action: Create Comment
	 */
	
	static function renderActionCreateComment($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_comment.tpl');
	}
	
	static function simulateActionCreateComment($params, DevblocksDictionaryDelegate $dict, $on_default) {
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);

		$out = sprintf(">>> Writing a comment:\n".
			"\n".
			"%s\n".
			"\n".
			""
			,
			rtrim($content)
		);
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$on_default);
		
		if(empty($on)) {
			return "[ERROR] The 'on' field is not set.";
		}
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			$out .= ">>> On:\n";
			
			foreach($on_objects as $on_object) {
				$on_object_context = Extension_DevblocksContext::get($on_object->_context);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object->_label . "\n";
			}
			$out .= "\n";
		}

		// Notify
		
		if(!empty($notify_worker_ids)) {
			$out .= ">>> Notifying:\n";
			foreach($notify_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= " * " . $worker->getName() . "\n";
				}
			}
			$out .= "\n";
		}
		
		// Links
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
		return rtrim($out);
	}
	
	static function runActionCreateComment($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		// Event
		$trigger = $dict->__trigger; /* @var $trigger Model_TriggerEvent */
		$event = $trigger->getEvent();

		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);

		// Fields
		
		$fields = array(
			DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
			DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $content,
		);
		
		$comment_id = null;
		
		// On: Are we linking these comments to something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				$fields[DAO_Comment::CONTEXT] = $on_object->_context;
				$fields[DAO_Comment::CONTEXT_ID] = $on_object->id;
				$comment_id = DAO_Comment::create($fields, $notify_worker_ids);
				
				// Connection
				DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_COMMENT, $comment_id, $params, $dict);
			}
		}
		
		return $comment_id;
	}
	
	static function renderActionScheduleTicketReply() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::events/model/ticket/action_schedule_email_recipients.tpl');
	}
	
	static function runActionScheduleTicketReply($params, DevblocksDictionaryDelegate $dict, $ticket_id, $message_id) {
		@$delivery_date_relative = $params['delivery_date'];
		
		if(false == ($delivery_date = strtotime($delivery_date_relative)))
			$delivery_date = time();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);
		
		$fields = array(
			DAO_MailQueue::TYPE => Model_MailQueue::TYPE_TICKET_REPLY,
			DAO_MailQueue::IS_QUEUED => 1,
			//DAO_MailQueue::HINT_TO => implode($dict->recipients),
			DAO_MailQueue::HINT_TO => '(recipients)',
			DAO_MailQueue::SUBJECT => $dict->ticket_subject,
			DAO_MailQueue::BODY => $content,
			DAO_MailQueue::PARAMS_JSON => json_encode(array(
				'in_reply_message_id' => $message_id,
				'is_broadcast' => 1,
			)),
			DAO_MailQueue::TICKET_ID => $ticket_id,
			DAO_MailQueue::WORKER_ID => 0,
			DAO_MailQueue::UPDATED => time(),
			DAO_MailQueue::QUEUE_DELIVERY_DATE => $delivery_date,
		);
		$queue_id = DAO_MailQueue::create($fields);
	}

	/*
	 * Action: Set Ticket Importance
	 */
	
	static function renderActionSetTicketImportance($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_number.tpl');
	}
	
	static function simulateActionSetTicketImportance($params, DevblocksDictionaryDelegate $dict, $default_on, $key) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		@$ticket_id = $dict->$default_on;
		
		// Importance
		
		@$importance = intval(
			$tpl_builder->build(
				DevblocksPlatform::importVar($params['value'], 'string', ''),
				$dict
			)
		);
		
		$importance = DevblocksPlatform::intClamp($importance, 0, 100);
		
		$out = sprintf(">>> Setting importance to %d\n", $importance);

		// Update dictionary
		$dict->$key = $importance;
		
		return $out;
	}
	
	static function runActionSetTicketImportance($params, DevblocksDictionaryDelegate $dict, $default_on, $key) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		@$ticket_id = $dict->$default_on;
		
		// Importance
		
		@$importance = intval(
			$tpl_builder->build(
				DevblocksPlatform::importVar($params['value'], 'string', ''),
				$dict
			)
		);
		
		$importance = DevblocksPlatform::intClamp($importance, 0, 100);

		DAO_Ticket::update($ticket_id, array(
			DAO_Ticket::IMPORTANCE => $importance,
		));
		
		// Update dictionary
		$dict->$key = $importance;
	}
	
	/*
	 * Action: Set Ticket Org
	 */
	
	static function renderActionSetTicketOrg($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$event = $trigger->getEvent();
		
		$values_to_contexts = $event->getValuesContexts($trigger);
		
		// Only keep address and ticket contexts
		if(is_array($values_to_contexts))
		foreach($values_to_contexts as $value_key => $value_data) {
			if(!isset($value_data['context'])
				|| !in_array($value_data['context'], array(CerberusContexts::CONTEXT_ADDRESS, CerberusContexts::CONTEXT_TICKET)))
					unset($values_to_contexts[$value_key]);
		}
		
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_org.tpl');
	}
	
	static function simulateActionSetTicketOrg($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$org = trim(
			$tpl_builder->build(
				DevblocksPlatform::importVar($params['org'], 'string', ''),
				$dict
			)
		);
		
		// Org
		
		$out = ">>> Setting organization:\n";
		
		if(empty($org)) {
			$out .= " * No org is being set. Skipping...";
			return $out;
		}
		
		$out .= $org ."\n";
		
		// Event

		$trigger = $dict->__trigger; /* @var $trigger Model_TriggerEvent */
		$event = $trigger->getEvent();
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'], 'string', $default_on);

		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			$out .= "\n>>> On:\n";
			
			foreach($on_objects as $on_object) {
				$on_object_context = Extension_DevblocksContext::get($on_object->_context);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object->_label . "\n";
			}
			$out .= "\n";
		}
		
		return $out;
	}
	
	static function runActionSetTicketOrg($params, DevblocksDictionaryDelegate $dict, $ticket_id, $values_prefix) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$org = trim(
			$tpl_builder->build(
				DevblocksPlatform::importVar($params['org'], 'string', ''),
				$dict
			)
		);

		// Event

		$trigger = $dict->__trigger; /* @var $trigger Model_TriggerEvent */
		$event = $trigger->getEvent();

		// Pull org record
		
		if(null == ($org_id = DAO_ContactOrg::lookup($org, true)) || empty($org_id)) {
			return;
		}
		
		// On:
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				switch($on_object->_context) {
					case CerberusContexts::CONTEXT_ADDRESS:
						DAO_Address::update($on_object->id, array(
							DAO_Address::CONTACT_ORG_ID => $org_id,
						));
						break;
						
					case CerberusContexts::CONTEXT_TICKET:
						DAO_Ticket::update($on_object->id, array(
							DAO_Ticket::ORG_ID => $org_id,
						));
						break;
				}
			}
		}
		
		/**
		 * Re-update org values in dictionary
		 */

		// Clear values in dictionary using $values_prefix
		
		$dict->scrubKeys($values_prefix);
		
		// Insert the new owner context
		
		$key = $values_prefix . '_context';
		$dict->$key = CerberusContexts::CONTEXT_ORG;
		
		$key = $values_prefix . 'id';
		$dict->$key = $org_id;
		
		$key = $values_prefix . '_label';
		$dict->$key;
	}
	
	/*
	 * Action: Set Ticket Owner
	 */
	
	static function renderActionSetTicketOwner($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAllActive());
		
		$event = $trigger->getEvent();
		$tpl->assign('values_to_contexts', $event->getValuesContexts($trigger));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_worker.tpl');
	}
	
	static function simulateActionSetTicketOwner($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$owner_id = $params['worker_id'];
		@$ticket_id = $dict->$default_on;
		
		if(empty($ticket_id))
			return;
		
		$out = ">>> Setting owner to:\n";

		// Placeholder?
		if(!is_numeric($owner_id) && $dict->exists($owner_id)) {
			@$owner_id = intval($dict->$owner_id);
		}
		
		if(empty($owner_id)) {
			$out .= "(nobody)\n";
			
		} else {
			if(null != ($owner_model = DAO_Worker::get($owner_id))) {
				$out .= $owner_model->getName() . "\n";
			}
		}
		
		return $out;
	}
	
	static function runActionSetTicketOwner($params, DevblocksDictionaryDelegate $dict, $default_on, $values_prefix) {
		@$owner_id = $params['worker_id'];
		@$ticket_id = $dict->$default_on;
		
		if(empty($ticket_id))
			return;
		
		// Placeholder?
		if(!is_numeric($owner_id) && $dict->exists($owner_id)) {
			@$owner_id = intval($dict->$owner_id);
		}
		
		if(empty($owner_id) || null != ($owner_model = DAO_Worker::get($owner_id))) {
			DAO_Ticket::update($ticket_id, array(
				DAO_Ticket::OWNER_ID => $owner_id,
			));
		}
		
		/**
		 * Re-update owner values in dictionary
		 */

		// Clear values in dictionary using $values_prefix
		
		$dict->scrubKeys($values_prefix);
		
		// Insert the new owner context
		
		$key = $values_prefix . '_context';
		$dict->$key = CerberusContexts::CONTEXT_WORKER;
		
		$key = $values_prefix . 'id';
		$dict->$key = $owner_id;
		
		$key = $values_prefix . '_label';
		$dict->$key;
	}

	/*
	 * Action: Add Recipients
	 */
	
	static function renderActionAddRecipients($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_picker_email_addresses.tpl');
	}
	
	static function _getObjectsFromDictVars($dict, $from_vars, $context) {
		$objects = array();
		
		// Include addys from variables
		if(isset($from_vars) && is_array($from_vars)) {
			foreach($from_vars as $from_var) {
				if(isset($dict->$from_var) && is_array($dict->$from_var)) {
					foreach($dict->$from_var as $key => $object) {
						if($object instanceof DevblocksDictionaryDelegate) {
							if(!$context || $object->_context == $context) {
								$objects[] = $object;
							}
						}
					}
				}
			}
		}
		
		return $objects;
	}

	static function simulateActionAddRecipients($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$recipients = array();

		$email_addresses_str = $tpl_builder->build(
			DevblocksPlatform::importVar($params['recipients'],'string',''),
			$dict
		);
		
		if(false != ($parsed_recipients = CerberusMail::parseRfcAddresses($email_addresses_str, true)) && is_array($recipients))
			$recipients = DevblocksPlatform::extractArrayValues($parsed_recipients, 'email', true);
		
		// Include addys from variables
		
		@$from_vars = DevblocksPlatform::importVar($params['from_vars'],'array',array());

		if(false != ($objects = self::_getObjectsFromDictVars($dict, $from_vars, CerberusContexts::CONTEXT_ADDRESS)))
			foreach($objects as $object)
				$recipients[] = $object->address;
		
		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Recipients
		
		$out = ">>> Adding recipients:\n";
		
		if(!is_array($recipients) || empty($recipients)) {
			$out .= " * No recipients are being set. Skipping...";
			return $out;
		}
		
		// Iterate addys
			
		foreach($recipients as $addy) {
			if(null != ($addy_model = DAO_Address::lookupAddress($addy, true))) {
				$out .= " * " . $addy_model->getNameWithEmail() . "\n";
			}
		}
		
		return $out;
	}
	
	static function runActionAddRecipients($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$recipients = array();

		$email_addresses_str = $tpl_builder->build(
			DevblocksPlatform::importVar($params['recipients'],'string',''),
			$dict
		);
		
		if(false != ($parsed_recipients = CerberusMail::parseRfcAddresses($email_addresses_str, true)) && is_array($recipients))
			$recipients = DevblocksPlatform::extractArrayValues($parsed_recipients, 'email', true);
		
		// Include addys from variables
		
		@$from_vars = DevblocksPlatform::importVar($params['from_vars'],'array',array());

		if(false != ($objects = self::_getObjectsFromDictVars($dict, $from_vars, CerberusContexts::CONTEXT_ADDRESS)))
			foreach($objects as $object)
				$recipients[] = $object->address;
		
		if(!is_array($recipients) || empty($recipients))
			return;
		
		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Action
		
		$ticket_id = $dict->$default_on;
		
		if(is_array($recipients))
		foreach($recipients as $addy) {
			DAO_Ticket::createRequester($addy, $ticket_id);
		}
	}
	
	/*
	 * Action: Remove Recipients
	 */
	
	static function renderActionRemoveRecipients($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_picker_email_addresses.tpl');
	}

	static function simulateActionRemoveRecipients($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$recipients = array();

		$email_addresses_str = $tpl_builder->build(
			DevblocksPlatform::importVar($params['recipients'],'string',''),
			$dict
		);
		
		if(false != ($parsed_recipients = CerberusMail::parseRfcAddresses($email_addresses_str, true)) && is_array($recipients))
			$recipients = DevblocksPlatform::extractArrayValues($parsed_recipients, 'email', true);
		
		// Include addys from variables
		
		@$from_vars = DevblocksPlatform::importVar($params['from_vars'],'array',array());

		if(false != ($objects = self::_getObjectsFromDictVars($dict, $from_vars, CerberusContexts::CONTEXT_ADDRESS)))
			foreach($objects as $object)
				$recipients[] = $object->address;
		
		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Recipients
		
		$out = ">>> Removing recipients:\n";
		
		if(!is_array($recipients) || empty($recipients)) {
			$out .= " * No recipients are being set. Skipping...";
			return $out;
		}
		
		// Iterate addys
			
		foreach($recipients as $addy) {
			if(null != ($addy_model = DAO_Address::lookupAddress($addy, true))) {
				$out .= " * " . $addy_model->getNameWithEmail() . "\n";
			}
		}
		
		return $out;
	}
	
	static function runActionRemoveRecipients($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$recipients = array();

		$email_addresses_str = $tpl_builder->build(
			DevblocksPlatform::importVar($params['recipients'],'string',''),
			$dict
		);
		
		if(false != ($parsed_recipients = CerberusMail::parseRfcAddresses($email_addresses_str, true)) && is_array($recipients))
			$recipients = DevblocksPlatform::extractArrayValues($parsed_recipients, 'email', true);

		// Include addys from variables
		
		@$from_vars = DevblocksPlatform::importVar($params['from_vars'],'array',array());

		if(false != ($objects = self::_getObjectsFromDictVars($dict, $from_vars, CerberusContexts::CONTEXT_ADDRESS)))
			foreach($objects as $object)
				$recipients[] = $object->address;
		
		if(!is_array($recipients) || empty($recipients))
			return;
		
		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Action
		
		$ticket_id = $dict->$default_on;
		
		if(is_array($recipients))
		foreach($recipients as $addy) {
			// [TODO] This could be more efficient
			if(false != ($addy_model = DAO_Address::lookupAddress($addy, true))) {
				DAO_Ticket::deleteRequester($ticket_id, $addy_model->id);
			}
		}
	}
	
	/*
	 * Action: Add Watchers
	 */
	
	static function renderActionAddWatchers($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAllActive());
		
		$event = $trigger->getEvent();
		$tpl->assign('values_to_contexts', $event->getValuesContexts($trigger));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_add_watchers.tpl');
	}

	static function simulateActionAddWatchers($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);

		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Watchers
		
		$out = ">>> Adding watchers:\n";
		
		if(!is_array($worker_ids) || empty($worker_ids)) {
			$out .= " * No watchers are being set. Skipping...";
			return $out;
		}
			
		// Iterate workers
			
		foreach($worker_ids as $worker_id) {
			if(null != ($worker = DAO_Worker::get($worker_id))) {
				$out .= " * " . $worker->getName() . "\n";
			}
		}
		
		$out .= "\n";
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'], 'string', $default_on);

		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			$out .= ">>> On:\n";
			
			foreach($on_objects as $on_object) {
				$on_object_context = Extension_DevblocksContext::get($on_object->_context);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object->_label . "\n";
			}
			$out .= "\n";
		}
		
		return $out;
	}
	
	static function runActionAddWatchers($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);
	
		if(!is_array($worker_ids) || empty($worker_ids))
			return;

		// Event
		
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// On: Are we watching something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				CerberusContexts::addWatchers($on_object->_context, $on_object->id, $worker_ids);
			}
		}
	}
	
	/*
	 * Action: Create Notification
	 */
	
	static function renderActionCreateNotification($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workers', DAO_Worker::getAll());

		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_notification.tpl');
	}
	
	static function simulateActionCreateNotification($params, DevblocksDictionaryDelegate $dict, $default_on) {
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);

		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		$out = sprintf(">>> Sending a notification:\n".
			"\n".
			"%s\n".
			""
			,
			rtrim($content)
		);
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		if(!empty($on)) {
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
			}
			
		} elseif(!empty($params['url'])) {
			$out .= sprintf("\n>>> Link to:\n%s\n",
				$params['url']
			);
			
		}
		
		// Notify
		
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		if(!empty($notify_worker_ids)) {
			$out .= "\n>>> Notifying:\n";
			
			foreach($notify_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= " * " . $worker->getName() . "\n";
				}
			}
		}
		
		return $out;
	}
	
	static function runActionCreateNotification($params, DevblocksDictionaryDelegate $dict, $default_on=null) {
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Notifications
		
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		// Only notify an individual worker once
		$notify_worker_ids = array_unique($notify_worker_ids);
		
		if(!is_array($notify_worker_ids) || empty($notify_worker_ids))
			return;
		
		// Template
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);
		@$url = DevblocksPlatform::importVar($params['url'],'string','');
		
		// On: Are we notifying about something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		if(empty($on) && !empty($url)) {
			$entry = array(
				//{{message}}
				'message' => 'activities.custom.other',
				'variables' => array(
					'message' => $content,
					),
				'urls' => array(
					'message' => $url,
					)
			);
			
			if(is_array($notify_worker_ids))
			foreach($notify_worker_ids as $notify_worker_id) {
				$fields = array(
					DAO_Notification::CONTEXT => '',
					DAO_Notification::CONTEXT_ID => 0,
					DAO_Notification::WORKER_ID => $notify_worker_id,
					DAO_Notification::CREATED_DATE => time(),
					DAO_Notification::ACTIVITY_POINT => 'custom.other',
					DAO_Notification::ENTRY_JSON => json_encode($entry),
				);
				$notification_id = DAO_Notification::create($fields);
			}
			
		} elseif (!empty($on)) {
			$notify_contexts = array();
			
			if(!empty($on)) {
				$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
				
				@$on_objects = $on_result['objects'];
	
				if(is_array($on_objects))
				foreach($on_objects as $on_object) {
						$notify_contexts[] = array($on_object->_context, $on_object->id, $on_object->_label);
				}
			}
			
			// Send notifications
			if(!empty($notify_contexts)) {
				if(is_array($notify_contexts))
				foreach($notify_contexts as $notify_context_data) {
					$entry = array(
						//{{message}}
						'message' => 'activities.custom.other',
						'variables' => array(
							'message' => $content,
							),
						'urls' => array(
							'message' => sprintf("ctx://%s:%d", $notify_context_data[0], $notify_context_data[1]),
							)
					);
					
					if(is_array($notify_worker_ids))
					foreach($notify_worker_ids as $notify_worker_id) {
						$fields = array(
							DAO_Notification::CONTEXT => $notify_context_data[0],
							DAO_Notification::CONTEXT_ID => $notify_context_data[1],
							DAO_Notification::WORKER_ID => $notify_worker_id,
							DAO_Notification::CREATED_DATE => time(),
							DAO_Notification::ACTIVITY_POINT => 'custom.other',
							DAO_Notification::ENTRY_JSON => json_encode($entry),
						);
						$notification_id = DAO_Notification::create($fields);
					}
				}
			}
		}
		
		// Clear notification cache
		if(is_array($notify_worker_ids))
		foreach($notify_worker_ids as $notify_worker_id) {
			DAO_Notification::clearCountCache($notify_worker_id);
		
		return isset($notification_id) ? $notification_id : false;
		}
	}
	
	/*
	 * Action: Create Message Sticky Note
	 */
	
	static function renderActionCreateMessageStickyNote($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$tpl->assign('workers', DAO_Worker::getAll());

		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		
		// Only keep message contextx
		if(is_array($values_to_contexts))
		foreach($values_to_contexts as $value_key => $value_data) {
			if(!isset($value_data['context'])
				|| !in_array($value_data['context'], array(CerberusContexts::CONTEXT_MESSAGE)))
					unset($values_to_contexts[$value_key]);
		}
		
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_message_sticky_note.tpl');
	}
	
	static function simulateActionCreateMessageStickyNote($params, DevblocksDictionaryDelegate $dict, $default_on) {
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);

		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		$out = sprintf(">>> Creating a message sticky note:\n".
			"\n".
			"%s\n".
			""
			,
			rtrim($content)
		);
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		if(!empty($on)) {
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object->_context);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object->_label . "\n";
				}
			}
		}
		
		// Notify
		
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		if(!empty($notify_worker_ids)) {
			$out .= "\n>>> Notifying:\n";
			
			foreach($notify_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= " * " . $worker->getName() . "\n";
				}
			}
		}
		
		return $out;
	}
	
	static function runActionCreateMessageStickyNote($params, DevblocksDictionaryDelegate $dict, $default_on=null) {
		$trigger = $dict->__trigger;
		$event = $trigger->getEvent();
		
		// Notifications
		
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		// Only notify an individual worker once
		
		$notify_worker_ids = array_unique($notify_worker_ids);
		
		// Template
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);
		
		$notify_contexts = array();
		
		// On: Are we notifying about something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);

		if(!empty($on)) {
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			
			@$on_objects = $on_result['objects'];

			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					$notify_contexts[] = array($on_object->_context, $on_object->id);
				}
			}
		}
		
		if(!empty($notify_contexts)) {
			if(is_array($notify_contexts))
			foreach($notify_contexts as $notify_context_data) {
				$fields = array(
					DAO_Comment::COMMENT => $content,
					DAO_Comment::CONTEXT => $notify_context_data[0],
					DAO_Comment::CONTEXT_ID => $notify_context_data[1],
					DAO_Comment::CREATED => time(),
					DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
					DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				);
				$note_id = DAO_Comment::create($fields, $notify_worker_ids);
			}
		}
		
		return isset($note_id) ? $note_id : false;
	}
	
	/*
	 * Action: Create Task
	 */
	
	static function renderActionCreateTask($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Context placeholders
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_TASK, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_task.tpl');
	}
	
	static function simulateActionCreateTask($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$trigger = $dict->__trigger;
		
		$due_date = $params['due_date'];

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$title = $tpl_builder->build($params['title'], $dict);

		$due_date = $tpl_builder->build($params['due_date'], $dict);
		
		if(!is_numeric($due_date))
			$due_date = intval(@strtotime($due_date));
		
		$comment = $tpl_builder->build($params['comment'], $dict);

		$out = sprintf(">>> Creating task\n".
			"Title: %s\n".
			"Due Date: %s (%s)\n".
			"",
			$title,
			(!empty($due_date) ? date("Y-m-d h:ia", $due_date) : 'none'),
			$params['due_date']
		);

		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			$out .= ">>> Adding watchers to task:\n";
			foreach($watcher_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= ' * ' . $worker->getName() . "\n";
				}
			}
			$out .= "\n";
		}
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on task\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Links
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			self::runActionCreateTask($params, $dict, $default_on);
		}

		return $out;
	}
	
	static function runActionCreateTask($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$trigger = $dict->__trigger;

		$due_date = $params['due_date'];

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$title = $tpl_builder->build($params['title'], $dict);
		
		$due_date = $tpl_builder->build($params['due_date'], $dict);
		
		if(!is_numeric($due_date))
			$due_date = intval(@strtotime($due_date));
		
		$comment = $tpl_builder->build($params['comment'], $dict);

		$fields = array(
			DAO_Task::TITLE => $title,
			DAO_Task::UPDATED_DATE => time(),
			DAO_Task::DUE_DATE => $due_date,
		);
		
		if(false == ($task_id = DAO_Task::create($fields)))
			return false;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_TASK, $task_id, $params, $dict);
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $task_id, $watcher_worker_ids);
		}
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
				DAO_Comment::CONTEXT_ID => $task_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}
		
		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_TASK, $task_id, $params, $dict);
		
		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_TASK, $task_id, $params, $dict);

		return $task_id;
	}
	
	/*
	 * Action: Create Ticket
	 */
	
	static function renderActionCreateTicket($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('groups', DAO_Group::getAll());
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);

		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_TICKET, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_ticket.tpl');
	}
	
	static function simulateActionCreateTicket($params, DevblocksDictionaryDelegate $dict) {
		$trigger = $dict->__trigger;
		
		@$group_id = $params['group_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;

		$translate = DevblocksPlatform::getTranslationService();
		$workers = DAO_Worker::getAll();
		
		$group_replyto = $group->getReplyTo();

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$requesters = $tpl_builder->build($params['requesters'], $dict);
		$subject = $tpl_builder->build($params['subject'], $dict);
		$content = $tpl_builder->build($params['content'], $dict);
		
		@$status_id = $params['status_id'];
		@$reopen_at = $params['reopen_at'];
		@$owner_id = $params['owner_id'];
		
		$out = sprintf(">>> Creating ticket\n".
			"Group: %s <%s>\n".
			"Requesters: %s\n".
			"Subject: %s\n".
			"",
			$group->name,
			$group_replyto->email,
			$requesters,
			$subject
		);
		
		$out .= sprintf("Status: %s\n",
			(Model_Ticket::STATUS_CLOSED==$status_id) ? $translate->_('status.closed') : ((Model_Ticket::STATUS_WAITING == $status_id) ? $translate->_('status.waiting') : $translate->_('status.open'))
		);
		
		if(!empty($owner_id) && isset($workers[$owner_id])) {
			$out .= sprintf("Owner: %s\n",
				$workers[$owner_id]->getName()
			);
		}
		
		if(!empty($status_id) && !empty($reopen_at))
			$out .= sprintf("Reopen at: %s\n", $reopen_at);

		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		// Content
		$out .= sprintf(
			"\n".
			">>> Message:\n".
			"%s\n".
			"\n",
			$content
		);
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			$out .= ">>> Adding watchers to ticket:\n";
			foreach($watcher_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= ' * ' . $worker->getName() . "\n";
				}
			}
			$out .= "\n";
		}
		
		// Connection
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetLinks($params, $dict);
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			self::runActionCreateTicket($params, $dict);
		}
		
		return $out;
	}
	
	static function runActionCreateTicket($params, DevblocksDictionaryDelegate $dict) {
		@$group_id = $params['group_id'];
		@$status_id = $params['status_id'];
		@$reopen_at = $params['reopen_at'];
		@$owner_id = $params['owner_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$group_replyto = $group->getReplyTo();
		
		$workers = DAO_Worker::getAll();
			
		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$requesters = $tpl_builder->build($params['requesters'], $dict);
		$subject = $tpl_builder->build($params['subject'], $dict);
		$content = $tpl_builder->build($params['content'], $dict);
				
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

		// [TODO] Fix this
		$message->body = sprintf(
			"(... This message was manually created by a bot on behalf of the requesters ...)\r\n"
		);

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
			'worker_id' => 0,
			'status_id' => $status_id,
			'owner_id' => $owner_id,
			'ticket_reopen' => $reopen_at,
		);
		
		// Watchers
		
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $watcher_worker_ids);
		}

		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_TICKET, $ticket_id, $params, $dict);
		
		// Connection
		DevblocksEventHelper::runActionCreateRecordSetLinks(CerberusContexts::CONTEXT_TICKET, $ticket_id, $params, $dict);

		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_TICKET, $ticket_id, $params, $dict);
		
		// Create the ticket
		CerberusMail::sendTicketMessage($properties);
		
		return $ticket_id;
	}
	
	/*
	 * Action: Send Email
	 */
	
	static function renderActionSendEmail($trigger, $placeholders=array()) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$replyto_default = DAO_AddressOutgoing::getDefault();
		$tpl->assign('replyto_default', $replyto_default);
		
		$replyto_addresses = DAO_AddressOutgoing::getAll();
		$tpl->assign('replyto_addresses', $replyto_addresses);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->assign('placeholders', $placeholders);
		
		$html_templates = DAO_MailHtmlTemplate::getAll();
		$tpl->assign('html_templates', $html_templates);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_send_email.tpl');
	}
	
	static private function _getEmailsFromTokens($params, $dict, $string_key, $var_key) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$results = [];
		@$vars = @$params[$var_key];
		
		// To
		
		if(isset($params[$string_key]) && !empty($params[$string_key])) {
			if(false !== ($output = $tpl_builder->build($params[$string_key], $dict))) {
				$results = DevblocksPlatform::parseCsvString($output);
			}
		}

		if(is_array($vars))
		foreach($vars as $var) {
			if(!isset($dict->$var))
				continue;
			
			// Security check
			if(substr($var,0,4) != 'var_')
				continue;
			
			$ids = [];
			
			if(is_array($dict->$var))
				$ids = array_keys($dict->$var);

			if(empty($ids))
				continue;
			
			$addresses = DAO_Address::getWhere(sprintf("%s IN (%s)",
				DAO_Address::ID,
				implode(",", $ids)
			));
			
			if(is_array($addresses))
			foreach($addresses as $addy) { /* @var $addy Model_Address */
				$results[] = $addy->email;
			}
		}
		
		return $results;
	}
	
	static function simulateActionSendEmail($params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		@$trigger = $dict->__trigger;
		
		$to = self::_getEmailsFromTokens($params, $dict, 'to', 'to_var');
		$cc = self::_getEmailsFromTokens($params, $dict, 'cc', 'cc_var');
		$bcc = self::_getEmailsFromTokens($params, $dict, 'bcc', 'bcc_var');

		$replyto_addresses = DAO_AddressOutgoing::getAll();
		$replyto_default = DAO_AddressOutgoing::getDefault();
		
		if(empty($replyto_default))
			return "[ERROR] There is no default sender address.  Please configure one from Setup->Mail";
		
		@$from_address_id = $params['from_address_id'];
		
		if(!empty($from_address_id) && (!is_numeric($from_address_id) || false !== strpos($from_address_id, ','))) {
			$from_address_id = 0;
			$from_placeholders = DevblocksPlatform::parseCsvString($params['from_address_id']);
			
			foreach($from_placeholders as $from_placeholder) {
				if(!empty($from_address_id))
					continue;

				if(isset($dict->$from_placeholder)) {
					$possible_from_id = $dict->$from_placeholder;
					
					if(isset($replyto_addresses[$possible_from_id]))
						$from_address_id = $possible_from_id;
				}
			}
		}
		
		if(empty($from_address_id) || !isset($replyto_addresses[$from_address_id]))
			$from_address_id = $replyto_default->address_id;

		if(empty($from_address_id)) {
			return "[ERROR] The 'from' address is invalid.";
		}
		
		if(empty($to)) {
			return "[ERROR] The 'to' field has no recipients.";
		}
		
		$to = array_unique($to);
		$bcc = array_unique($bcc);
		
		if(false === ($subject = $tpl_builder->build($params['subject'], $dict))) {
			return "[ERROR] The 'subject' field has invalid placeholders.";
		}
		
		if(false === ($headers_string = isset($params['headers']) ? $tpl_builder->build($params['headers'], $dict) : '')) {
			return "[ERROR] The 'headers' field has invalid placeholders.";
		}
		
		$headers = DevblocksPlatform::parseCrlfString($headers_string);
		
		if(false === ($content = $tpl_builder->build($params['content'], $dict))) {
			return "[ERROR] The 'content' field has invalid placeholders.";
		}
		
		$out = sprintf(">>> Sending email\n".
			"To: %s\n".
			"%s".
			"%s".
			"From: %s\n".
			"Subject: %s\n".
			"%s".
			"\n".
			"%s\n",
			implode(",\n  ", $to),
			(!empty($cc) ? ('Cc: ' . implode(",\n  ", $cc) . "\n") : ''),
			(!empty($bcc) ? ('Bcc: ' . implode(",\n  ", $bcc) . "\n") : ''),
			$replyto_addresses[$from_address_id]->email,
			$subject,
			(!empty($headers) ? (implode("\n", $headers) . "\n") : ''),
			$content
		);
		
		// Attachment list variables
		
		if(isset($params['attachment_vars']) && is_array($params['attachment_vars'])) {
			$out = rtrim($out,"\n") . "\n\n>>> Attaching files from variables:\n";
			
			foreach($params['attachment_vars'] as $attachment_var) {
				if(false != ($attachments = $dict->$attachment_var) && is_array($attachments)) {
					foreach($attachments as $attachment_id => $attachment) {
						$out .= " * " . $attachment->name . ' (' . DevblocksPlatform::strPrettyBytes($attachment->size) . ')' . "\n";
					}
				}
			}
		}
		
		// Attachment bundles
		
		if(isset($params['bundle_ids']) && is_array($params['bundle_ids'])) {
			$out = rtrim($out,"\n") . "\n\n>>> Attaching files from bundles:\n";
			
			$bundles = DAO_FileBundle::getIds($params['bundle_ids']);
			foreach($bundles as $bundle) {
				$attachments = $bundle->getAttachments();
				
				foreach($attachments as $attachment) {
					$out .= " * " . $attachment->name . "\n";
				}
			}
		}
		
		return $out;
	}
	
	static function runActionSendEmail($params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$trigger = $dict->__trigger;
		
		$to = self::_getEmailsFromTokens($params, $dict, 'to', 'to_var');
		$cc = self::_getEmailsFromTokens($params, $dict, 'cc', 'cc_var');
		$bcc = self::_getEmailsFromTokens($params, $dict, 'bcc', 'bcc_var');
		
		// From
		
		$replyto_addresses = DAO_AddressOutgoing::getAll();
		$replyto_default = DAO_AddressOutgoing::getDefault();
		
		if(empty($replyto_default))
			return;
		
		@$from_address_id = $params['from_address_id'];
		
		if(!empty($from_address_id) && !is_numeric($from_address_id) || false !== strpos($from_address_id, ',')) {
			$from_address_id = 0;
			$from_placeholders = DevblocksPlatform::parseCsvString($params['from_address_id']);
			
			foreach($from_placeholders as $from_placeholder) {
				if(!empty($from_address_id))
					continue;

				if(isset($dict->$from_placeholder)) {
					$possible_from_id = $dict->$from_placeholder;
					
					if(isset($replyto_addresses[$possible_from_id]))
						$from_address_id = $possible_from_id;
				}
			}
		}
		
		if(empty($from_address_id) || !isset($replyto_addresses[$from_address_id]))
			$from_address_id = $replyto_default->address_id;
		
		if(empty($from_address_id))
			return;
		
		// Properties
		
		@$subject = $tpl_builder->build($params['subject'], $dict);
		@$content = $tpl_builder->build($params['content'], $dict);
		@$format = $params['format'];
		@$html_template_id = intval($params['html_template_id']);

		// Headers
		
		@$headers = DevblocksPlatform::parseCrlfString($tpl_builder->build($params['headers'], $dict));
		
		// Format
		switch($format) {
			case 'parsedown':
				
				// HTML template
				
				// Default to reply-to if empty
				if(!$html_template_id && false != ($replyto = $replyto_addresses[$from_address_id])) {
					$html_template = $replyto->getReplyHtmlTemplate();
					$html_template_id = $html_template->id;
				}
				break;
		}
		
		// Attachments
		
		$file_ids = array();
		
		// Attachment list variables
		
		if(isset($params['attachment_vars']) && is_array($params['attachment_vars'])) {
			foreach($params['attachment_vars'] as $attachment_var) {
				if(false != ($attachments = $dict->$attachment_var) && is_array($attachments)) {
					foreach($attachments as $attachment) {
						$file_ids[] = $attachment->id;
					}
				}
			}
		}
		
		// File bundles
		
		if(isset($params['bundle_ids']) && is_array($params['bundle_ids'])) {
			$bundles = DAO_FileBundle::getIds($params['bundle_ids']);
			foreach($bundles as $bundle) {
				$attachments = $bundle->getAttachments();
				
				foreach($attachments as $attachment) {
					$file_ids[] = $attachment->id;
				}
			}
		}
		
		// Send
		
		CerberusMail::quickSend(
			implode(', ', $to),
			$subject,
			$content,
			$replyto_addresses[$from_address_id]->email,
			$replyto_addresses[$from_address_id]->reply_personal,
			$headers,
			$format,
			$html_template_id,
			$file_ids,
			implode(', ', $cc),
			implode(', ', $bcc)
		);
	}
	
	/*
	 * Action: Send Email to Recipients
	 */

	static function simulateActionSendEmailRecipients($params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		// Headers
		
		if(false === ($headers_string = $tpl_builder->build($params['headers'], $dict)))
			return "[ERROR] The 'headers' field has invalid placeholders.";
		
		$headers = DevblocksPlatform::parseCrlfString($headers_string);
		
		// Content
		
		$content = $tpl_builder->build($params['content'], $dict);

		// Out
		
		$out = sprintf(">>> Sending email to recipients\n".
			"%s".
			"%s\n",
			(!empty($headers) ? (implode("\n", $headers) . "\n\n") : ''),
			$content
		);
		
		// Attachment list variables
		
		if(isset($params['attachment_vars']) && is_array($params['attachment_vars'])) {
			$out = rtrim($out,"\n") . "\n\n>>> Attaching files from variables:\n";
			
			foreach($params['attachment_vars'] as $attachment_var) {
				if(false != ($attachments = $dict->$attachment_var) && is_array($attachments)) {
					foreach($attachments as $attachment_id => $attachment) {
						$out .= " * " . $attachment->name . ' (' . DevblocksPlatform::strPrettyBytes($attachment->size) . ')' . "\n";
					}
				}
			}
		}
		
		// Attachment bundles

		if(isset($params['bundle_ids']) && is_array($params['bundle_ids'])) {
			$out = rtrim($out,"\n") . "\n\n>>> Attaching files:\n";
			
			$bundles = DAO_FileBundle::getIds($params['bundle_ids']);
			foreach($bundles as $bundle) {
				$attachments = $bundle->getAttachments();
				
				foreach($attachments as $attachment) {
					$out .= " * " . $attachment->name . "\n";
				}
			}
		}
		
		return $out;
	}

	/*
	 * Action: Relay Email
	 */
	
	// [TODO] Move this to an event parent so we can presume values
	
	static function renderActionRelayEmail($filter_to_worker_ids=array(), $show=array('owner','watchers','workers'), $content_token='content') {
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
	
	private static function _getActionRelayEmailListTo($params, DevblocksDictionaryDelegate $dict, $context, $context_id, $owner_id) {
		$relay_list = isset($params['to']) ? $params['to'] : array();
		$to_list = array();
		
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
				if(!in_array($watcher, $relay_list))
				$relay_list[] = $watcher;
			}
		}
		
		// Convert relay list to email addresses
		
		$trigger = $dict->__trigger; /* @var $trigger Model_TriggerEvent */
		
		if(is_array($relay_list))
		foreach($relay_list as $to) {
			
			// Worker models
			if($to instanceof Model_Worker) {
				$to_list[$to->email] = $to;
			
			// Variables
			} else if(is_string($to) && 'var_' == substr($to, 0, 4) && isset($trigger->variables[$to])) {
				switch(@$trigger->variables[$to]['type']) {
					case 'ctx_' . CerberusContexts::CONTEXT_WORKER:
						foreach($dict->$to as $also_to) {
							if($also_to instanceof DevblocksDictionaryDelegate) {
								if($also_to->_context == CerberusContexts::CONTEXT_WORKER) {
									if(null != ($worker = DAO_Worker::get($also_to->id)))
										$to_list[$also_to->address_address] = $worker;
								}
							}
						}
						break;
					
					case Model_CustomField::TYPE_WORKER:
						@$worker_id = $dict->$to;
						
						if(empty($worker_id))
							continue;
						
						if(null == ($worker = DAO_Worker::get($dict->$to)))
							continue;
						
						$to_list[$worker->getEmailString()] = $worker;
						break;
				}
				
			// Email address strings
			} elseif (is_string($to)) {
				if(null == ($worker_address = DAO_AddressToWorker::getByEmail($to)))
					continue;
					
				if(null == ($worker = $worker_address->getWorker()))
					continue;
				
				$to_list[$worker_address->getEmailAsString()] = $worker;
			}
		}
		
		return $to_list;
	}
	
	static function simulateActionRelayEmail($params, DevblocksDictionaryDelegate $dict, $context, $context_id, $group_id, $bucket_id, $message_id, $owner_id, $sender_email, $sender_name, $subject) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		$subject = $tpl_builder->build($params['subject'], $dict);
		$content = $tpl_builder->build($params['content'], $dict);
		
		$to_list = self::_getActionRelayEmailListTo($params, $dict, $context, $context_id, $owner_id);
		
		$out = sprintf(">>> Relaying email\n".
			"To: %s\n".
			"Subject: %s\n".
			"\n".
			"%s",
			(!empty($to_list) ? (implode("; ", array_keys($to_list))) : ''),
			$subject,
			$content
		);
		
		return $out;
	}
	
	
	static function runActionRelayEmail($params, DevblocksDictionaryDelegate $dict, $context, $context_id, $group_id, $bucket_id, $message_id, $owner_id, $sender_email, $sender_name, $subject) {
		$logger = DevblocksPlatform::getConsoleLog('Bot');
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$mail_service = DevblocksPlatform::getMailService();
		$settings = DevblocksPlatform::getPluginSettingsService();
		
		$relay_spoof_from = $settings->get('cerberusweb.core', CerberusSettings::RELAY_SPOOF_FROM, CerberusSettingsDefaults::RELAY_SPOOF_FROM);
		
		// Our main record can either be a comment or a message
		$comment_id = (isset($dict->comment_id) && !empty($dict->comment_id)) ? $dict->comment_id : null;
		
		if(empty($group_id) || null == ($group = DAO_Group::get($group_id))) {
			$logger->error("Can't load the ticket's group. Aborting action.");
			return;
		}
		
		if($relay_spoof_from) {
			$replyto = $group->getReplyTo($bucket_id);
		} else {
			$replyto = DAO_AddressOutgoing::getDefault();
		}

		// Attachments
		$attachments = array();
		
		if(isset($params['include_attachments']) && !empty($params['include_attachments'])) {
			// If our main record is a comment, use those attachments instead
			if($comment_id) {
				$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_COMMENT, $comment_id);
				
			} elseif($message_id) {
				$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MESSAGE, $message_id);
			}
		}
		
		$to_list = self::_getActionRelayEmailListTo($params, $dict, $context, $context_id, $owner_id);
		
		if(is_array($to_list))
		foreach($to_list as $to => $worker) {
			try {
				$mail = $mail_service->createMessage();
				
				$mail->setTo(array($to));
	
				$headers = $mail->getHeaders(); /* @var $headers Swift_Mime_Header */

				if($relay_spoof_from) {
					$mail->setFrom($sender_email, !empty($sender_name) ? $sender_name : null);
					$mail->setReplyTo($replyto->email);
					
				} else {
					$replyto_personal = $replyto->getReplyPersonal($worker);
					
					if(!empty($replyto_personal)) {
						$mail->setFrom($replyto->email, !empty($replyto_personal) ? $replyto_personal : null);
						$mail->setReplyTo($replyto->email, !empty($replyto_personal) ? $replyto_personal : null);
						
					} else {
						$mail->setFrom($replyto->email);
						$mail->setReplyTo($replyto->email);
					}
				}
				
				if(!isset($params['subject']) || empty($params['subject'])) {
					$mail->setSubject($subject);
				} else {
					$subject = $tpl_builder->build($params['subject'], $dict);
					$mail->setSubject($subject);
				}
	
				$headers->removeAll('message-id');

				// Sign the message so we can verify a future relay response
				$sign = sha1($message_id . $worker->id . APP_DB_PASS);
				$headers->addTextHeader('Message-Id', sprintf("<%s%s%s@cerb>", mt_rand(1000,9999), $sign, dechex($message_id)));
				
				$headers->addTextHeader('X-CerberusRedirect','1');
	
				$content = $tpl_builder->build($params['content'], $dict);
				
				$mail->setBody($content);
				
				// Files
				if(!empty($attachments) && is_array($attachments))
				foreach($attachments as $file_id => $file) { /* @var $file Model_Attachment */
					if(false !== ($fp = DevblocksPlatform::getTempFile())) {
						if(false !== $file->getFileContents($fp)) {
							$attach = Swift_Attachment::fromPath(DevblocksPlatform::getTempFileInfo($fp), $file->mime_type);
							$attach->setFilename($file->name);
							$mail->attach($attach);
							fclose($fp);
						}
					}
				}
				
				$result = $mail_service->send($mail);
				unset($mail);
				
				/*
				 * Log activity (ticket.message.relay)
				 */
				if($context == CerberusContexts::CONTEXT_TICKET) {
					$entry = array(
						//{{actor}} relayed ticket {{target}} to {{worker}} ({{worker_email}})
						'message' => 'activities.ticket.message.relay',
						'variables' => array(
							'target' => sprintf("[%s] %s", $dict->ticket_mask, $dict->ticket_subject),
							'worker' => $worker->getName(),
							'worker_email' => $to,
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_TICKET, $context_id),
							'worker' => sprintf("ctx://%s:%d", CerberusContexts::CONTEXT_WORKER, $worker->id),
							)
					);
					CerberusContexts::logActivity('ticket.message.relay', CerberusContexts::CONTEXT_TICKET, $context_id, $entry);
				}
				
				if(!$result)
					return false;
				
			} catch (Exception $e) {
				
			}
		}
	}
	
	static function onContexts($on_keys, $values_to_contexts, DevblocksDictionaryDelegate $dict, $load_objects=true) {
		$result = array();
		
		if(!empty($on_keys)) {
			if(!is_array($on_keys))
				$on_keys = array($on_keys);

			$vals = array();
			
			// [TODO] We could cache the output of this for the same $on
			//		It runs multiple times even on simple actions.
			
			foreach($on_keys as $on) {
				$on_value = null;
				@$is_polymorphic = $values_to_contexts[$on]['is_polymorphic'];

				// If we're given an explicit context_id (i.e. not the value of the key)
				if(isset($values_to_contexts[$on]['context_id'])) {
					if(is_numeric($values_to_contexts[$on]['context_id'])) {
						@$on_value = $values_to_contexts[$on]['context_id'];
						
					} else {
						$on_value_key = $values_to_contexts[$on]['context_id'];
						@$on_value = $dict->$on_value_key;
					}
				}

				// If we don't have a value yet, use the value of the given $on key
				if(empty($on_value))
					@$on_value = $dict->$on;

				// If we still don't have a value, skip this entry
				if(empty($on_value))
					continue;

				if(preg_match("#(.*)_watchers#", $on)) {
					if(is_array($on_value)) {
						if($load_objects) {
							$vals = $on_value;
						} else {
							$vals = array_keys($on_value);
						}
					}
					
				} else {
					if(!is_null($on))
						$vals = is_array($on_value) ? $on_value : array($on_value);
				}

				$ctx_ext = null;

				// If $on is a dynamic context, find the right context
				if($is_polymorphic) {
					// If we're given a key to check for the context, use it.
					if(isset($values_to_contexts[$on]['context'])) {
						$ctx_ext_key = $values_to_contexts[$on]['context'];
						$ctx_ext = $dict->$ctx_ext_key;
					}
					
				// Otherwise, check the explicit context
				} elseif(isset($values_to_contexts[$on]['context'])) {
					@$ctx_ext = $values_to_contexts[$on]['context'];
				}
				
				foreach($vals as $ctx_id => $ctx_object) {
					if(empty($ctx_object))
						continue;

					if(!is_object($ctx_object)) {
						$ctx_id = $ctx_object;
					}
					
					if($load_objects) {
						$ctx_values = array();
						CerberusContexts::getContext($ctx_ext, $ctx_id, $null, $ctx_values);
						$ctx_object = new DevblocksDictionaryDelegate($ctx_values);
						
						if(!isset($result['objects']))
							$result['objects'] = array();
						
						$result['objects'][$ctx_ext.':'.$ctx_id] = $ctx_object;
						
					} else {
						if(is_numeric($ctx_id))
							$result[$ctx_ext.':'.$ctx_id] = true;
					}
					
				}
			}
		}
		
		if(!$load_objects)
			return array_keys($result);
		else
			return $result;
	}
	
	static function mergeWorkerVars($worker_ids, DevblocksDictionaryDelegate $dict, $include_inactive=false) {
		$workers = DAO_Worker::getAll();
		
		if(is_array($worker_ids))
		foreach($worker_ids as $k => $worker_id) {
			if(!is_numeric($worker_id)) {
				$key = $worker_id;
				@$val = $dict->$key;
				unset($worker_ids[$k]);
				
				if(!empty($val)) {
					if(preg_match("#(.*)_watchers#", $key)) {
						$worker_ids = array_merge($worker_ids, array_keys($val));
					} elseif(is_array($val) && preg_match("#var_(.*)#", $key)) {
						$worker_ids = array_merge($worker_ids, array_keys($val));
					} elseif(is_array($val)) {
						$worker_ids = array_merge($worker_ids, $val);
					} else {
						$worker_ids[] = $val;
					}
				}
			}
		}
		
		// Filter the worker IDs we're returning
		$worker_ids = array_filter($worker_ids, function($worker_id) use ($workers, $include_inactive) {
			@$worker = $workers[$worker_id];
			
			// Skip any invalid worker IDs
			if(empty($worker))
				return false;
			
			// Are we excluding inactive workers?
			if(!$include_inactive && $worker->is_disabled)
				return false;
			
			return true;
		});
		
		return array_unique($worker_ids);
	}
	
	static function getViewFromAbstractJson($token, $params, $trigger, $context) {
		@$worklist_model = $params['worklist_model'];
		
		$view_id = sprintf("_trigger_%d_%s_%s",
			$trigger->id,
			$token,
			uniqid()
		);
		
		// If the model is blank, initialize it
		if(empty($worklist_model)) {
			if(false == ($context_ext = Extension_DevblocksContext::get($context)))
				return;
			
			if(null == ($view = $context_ext->getChooserView($view_id)))
				return;
			
			$worklist_model = json_decode(C4_AbstractViewLoader::serializeViewToAbstractJson($view, $context), true);
		}
		
		return C4_AbstractViewLoader::unserializeViewFromAbstractJson($worklist_model, $view_id);
	}
	
	static function runActionSetListVariable($token, $context, $params, DevblocksDictionaryDelegate $dict) {
		$trigger = $dict->__trigger;
		
		if(null == ($view = DevblocksEventHelper::getViewFromAbstractJson($token, $params, $trigger, $context)))
			return;
		
		// Load values and ignore _labels and _types
		$view->setPlaceholderValues($dict->getDictionary(null, false));

		$view->persist();
		$view->setAutoPersist(false);
		
		// Save the generated view_id in the dictionary for reuse (paging, etc)
		$var_view_id_key = sprintf("%s_view_id", $token);
		$dict->$var_view_id_key = $view->id;
		
		// [TODO] Iterate through pages if over a certain list length?
		// [TODO] I believe we solved this by just setting ctx:id rows first
		//$view->renderLimit = (isset($params['limit']) && is_numeric($params['limit'])) ? intval($params['limit']) : 100;
		$view->renderLimit = 5000;
		$view->renderPage = 0;
		$view->renderTotal = false;
		
		if(isset($params['search_mode']) 
				&& $params['search_mode'] == 'quick_search'
				&& isset($params['quick_search'])) {
			$view->addParamsWithQuickSearch($params['quick_search']);
		}
		
		list($results) = $view->getData();
		
		if(!isset($dict->$token) || !is_array($dict->$token))
			$dict->$token = array();

		$old_ids = array_keys($dict->$token);
		$new_ids = array_keys($results);

		// Are we reducing the list?
		if(isset($params['limit']) && !empty($params['limit'])) {
			@$limit_to = intval($params['limit_count']);
			
			switch(@$params['limit']) {
				case 'first':
					if(count($new_ids) > $limit_to)
						$new_ids = array_slice($new_ids, 0, $limit_to);
					break;
					
				case 'last':
					if(count($new_ids) > $limit_to)
						$new_ids = array_slice($new_ids, -$limit_to);
					break;
					
				case 'random':
					if(count($new_ids) > $limit_to) {
						shuffle($new_ids);
						$new_ids = array_slice($new_ids, 0, $limit_to);
					}
					break;
			}
		}
		
		switch(@$params['mode']) {
			default:
			case 'add':
				$new_ids = array_merge($old_ids, $new_ids);
				break;
			case 'subtract':
				$new_ids = array_diff($old_ids, $new_ids);
				break;
			case 'replace':
				break;
		}
		
		// Remove any existing IDs in the token that aren't in the $new_ids (subtract/replace)
		
		@$array =& $dict->$token;
		
		if(is_array($array))
		foreach($array as $id => $object) {
			if(!in_array($id, $new_ids))
				unset($array[$id]);
		}
		
		$objects = array();
		
		// Preload these from DAO
		if(is_array($new_ids))
			$objects = $view->getDataAsObjects($new_ids);

		if(is_array($objects))
		foreach($new_ids as $new_id) {
			$object = isset($objects[$new_id]) ? $objects[$new_id] : null;
			
			if(!empty($object)) {
				$obj_labels = array();
				$obj_values = array();
				CerberusContexts::getContext($context, $object, $obj_labels, $obj_values, null, true);
				$array =& $dict->$token;
				$array[$new_id] = new DevblocksDictionaryDelegate($obj_values);
			}
		}
	}
};