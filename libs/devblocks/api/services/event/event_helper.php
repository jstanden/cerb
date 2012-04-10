<?php
class DevblocksEventHelper {
	public static function getVarValueToContextMap($trigger) {
		$values_to_contexts = array();
		
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
	
	static function simulateActionSetCustomField(Model_CustomField $custom_field, $value_key, $params, DevblocksDictionaryDelegate $dict, $context, $context_id) {
		@$field_id = $custom_field->id;
		
		if(empty($field_id) || empty($context) || empty($context_id))
			return;
		
		$out = '';
		
		$out .= sprintf(">>> Setting %s to:\n",
			$custom_field->name
		);
		
		switch($custom_field->type) {
			case Model_CustomField::TYPE_CHECKBOX:
				@$value = $params['value'];
				$out .= sprintf("%s\n",
					!empty($value) ? 'yes' : 'no'
				);
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
					$dict->$value_key.'_'.$field_id = $value;
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				$value = $params['value'];
				$value = strtotime($value);

				if(!empty($value)) {
					$out .= sprintf("%s (%s)\n",
						date('Y-m-d h:ip', $value),
						$params['value']
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

				$out .= sprintf("%s\n",
					implode(', ', $opts)
				);
				
				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = implode(',',$opts);
					
					$array =& $dict->$value_key;
					$array[$field_id] = $value;
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
					$dict->$value_key.'_'.$field_id = $worker_id;

					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
				
			default:
				//$this->runActionExtension($token, $trigger, $params, $dict);
				//$this->simulateActionExtension($token, $trigger, $params, $dict);
				break;
		}
		
		return $out;
	}
	
	static function runActionSetCustomField(Model_CustomField $custom_field, $value_key, $params, DevblocksDictionaryDelegate $dict, $context, $context_id) {
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
				$value = $builder->build($value, $dict);
				
				DAO_CustomFieldValue::setFieldValue($context, $context_id, $field_id, $value);

				if(!empty($value_key)) {
					$dict->$value_key.'_'.$field_id = $value;

					$array =& $dict->$value_key;
					$array[$field_id] = $value;
				}
				break;
			
			case Model_CustomField::TYPE_DATE:
				$value = $params['value'];
				$value = strtotime($value);
				
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
					$array[$field_id] = $value;
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
					$array[$field_id] = $value;
				}
				break;
				
			default:
				$this->runActionExtension($token, $trigger, $params, $dict);
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
	
	static function renderActionSetVariableWorker() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		// Workers
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Groups
		$tpl->assign('groups', DAO_Group::getAll());
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_worker.tpl');
	}
	
	static function renderActionSetListVariable($token, $trigger, $params, $context) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(null == ($view_model = DevblocksEventHelper::getParamsViewModel($token, $params, $trigger, $context)))
			return;
		
		// Force reload parameters (we can't trust the session)
		if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($view_model)))
			return;
		
		C4_AbstractViewLoader::setView($view->id, $view);
		
		$params['view_model'] = base64_encode(serialize($view_model));
		
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
				strtolower($context_ext->name),
				$token
			);
			
			$fields = array();
			$null = array();
			CerberusContexts::getContext($context_extid, null, $fields, $null, null, true);

			$out .= "\n";
			
			$counter = 0;
			foreach($objects as $object) {
				@$label = $object['_label'];
				
				$out .= sprintf(" [%d] %s\n",
					++$counter,
					$label ? $label : '(object)' 
				);
			}

			$obj_name = strtolower($context_ext->name);
			
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
				$out .= sprintf(" * %s.%s\n     %s\n",
					$obj_name,
					$k,
					$v
				);
			}
			
		} else {
			@$value = is_array($dict->$token) ? implode(',', $dict->$token) : $dict->$token;
			
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
				if(!isset($params['value']))
					break;
				
				$value = is_numeric($params['value']) ? $params['value'] : @strtotime($params['value']);
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_NUMBER:
				$value = intval($params['value']);
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_SINGLE_LINE:
			case Model_CustomField::TYPE_MULTI_LINE:
				if(!isset($params['value']))
					break;
				
				$tpl_builder = DevblocksPlatform::getTemplateBuilder();
				$value = $tpl_builder->build($params['value'], $dict);
				$dict->$token = $value;
				break;
				
			case Model_CustomField::TYPE_WORKER:
				@$worker_ids = $params['worker_id'];
				@$group_ids = $params['group_id'];
				@$mode = $params['mode'];
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
				
				// Filter
				$workers = DAO_Worker::getAll();
				
				if(!empty($opt_logged_in))
					$workers_online = DAO_Worker::getAllOnline();
				
				foreach($possible_workers as $k => $worker) {
					// Remove non-existent workers
					if(!isset($workers[$k])) {
						unset($possible_workers[$k]);
						continue;
					}
		
					// Filter to online workers
					if(!empty($opt_logged_in) && !isset($workers_online[$k])) {
						unset($possible_workers[$k]);
						continue;
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
						$ids = array_keys($possible_workers);
						shuffle($ids);
						$chosen_worker_id = reset($ids);
						break;
						
					// Sequential
					case 'seq':
						$key = sprintf("trigger.%d.counter", $trigger->id);
						
						$registry = DevblocksPlatform::getRegistryService();
						$count = intval($registry->get($key));
						
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
						$sql = sprintf("SELECT COUNT(id) AS hits, owner_id FROM ticket WHERE is_closed = 0 AND is_deleted = 0 AND is_waiting = 0 AND owner_id != 0 AND owner_id IN (%s) GROUP BY owner_id",
							implode(',', array_keys($possible_workers))
						);
						$results = $db->GetArray($sql);
						
						if(!empty($results))
						foreach($results as $row) {
							$worker_loads[$row['owner_id']] = intval($row['hits']);
						}
						
						asort($worker_loads);
						reset($worker_loads);
						
						$chosen_worker_id = key($worker_loads);
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
		
		$macros = DAO_TriggerEvent::getByOwner($trigger->owner_context, $trigger->owner_context_id);
		
		foreach($macros as $k => $macro) {
			if(!in_array($macro->event_point, $context_to_macros)) {
				unset($macros[$k]);
			}
		}
		
		$tpl->assign('macros', $macros);
		
		// Template
		
		$tpl->display('devblocks:cerberusweb.core::events/action_schedule_behavior.tpl');
	}
	
	static function simulateActionScheduleBehavior($params, DevblocksDictionaryDelegate $dict) {
		@$behavior_id = $params['behavior_id'];
		@$run_date = $params['run_date'];
		@$on_dupe = $params['on_dupe'];

		$trigger = $dict->_trigger;

		if(empty($behavior_id)) {
			return "[ERROR] No behavior is selected. Skipping...";
		}
		
		@$run_timestamp = strtotime($run_date);
		
		if(null == ($behavior = DAO_TriggerEvent::get($behavior_id)))
			return "[ERROR] Behavior does not exist. Skipping...";
		
		$out = sprintf(">>> Scheduling behavior\n".
			"Behavior: %s\n".
			"When: %s (%s)\n",
			$behavior->title,
			date('Y-m-d h:ip', $run_timestamp),
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
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object['_label'] . "\n";  
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
		
		@$run_timestamp = strtotime($run_date);
		
		if(empty($behavior_id))
			return FALSE;
		
		// Variables as parameters
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$vars = array();
		foreach($params as $k => $v) {
			if(substr($k,0,4) == 'var_') {
				$vars[$k] = $tpl_builder->build($v, $dict);
			}
		}
		
		// On
		
		@$on = DevblocksPlatform::importVar($params['on'],'string','');
		
		if(!empty($on)) {
			$trigger = $dict->_trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					if(!isset($on_object['id']) && empty($on_object['id']))
						continue;
					
					switch($on_dupe) {
						// Only keep first
						case 'first':
							// Keep the first, delete everything else, and don't add a new one
							$behaviors = DAO_ContextScheduledBehavior::getByContext($on_object['_context'], $on_object['id']);
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
							DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id, $on_object['_context'], $on_object['id']);
							break;
						
						// Allow dupes
						default:
							// Do nothing
							break;
					}
					
					
					$fields = array(
						DAO_ContextScheduledBehavior::CONTEXT => $on_object['_context'],
						DAO_ContextScheduledBehavior::CONTEXT_ID => $on_object['id'],
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
		
		$macros = DAO_TriggerEvent::getByOwner($trigger->owner_context, $trigger->owner_context_id);
		
		foreach($macros as $k => $macro) {
			if(!in_array($macro->event_point, $context_to_macros)) {
				unset($macros[$k]);
			}
		}
		
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
			$trigger = $dict->_trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= "\n>>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object['_label'] . "\n";  
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
			$trigger = $dict->_trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					DAO_ContextScheduledBehavior::deleteByBehavior($behavior_id, $on_object['_context'], $on_object['id']);
				}
			}
		}		
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

		$trigger = $dict->_trigger;
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
				$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object['_label'] . "\n";  
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
		
		return rtrim($out);
	}
	
	static function runActionCreateComment($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		// Event
		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$content = $tpl_builder->build($params['content'], $dict);

		// Fields
		
		$fields = array(
			DAO_Comment::ADDRESS_ID => 0,
			DAO_Comment::CREATED => time(),
			DAO_Comment::COMMENT => $content,
		);
		
		// On: Are we linking these comments to something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				$fields[DAO_Comment::CONTEXT] = $on_object['_context'];
				$fields[DAO_Comment::CONTEXT_ID] = $on_object['id'];
				$comment_id = DAO_Comment::create($fields, $notify_worker_ids);
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
	
	static function runActionSetTicketOwner($params, DevblocksDictionaryDelegate $dict, $ticket_id, $values_prefix) {
		@$owner_id = $params['worker_id'];
		
		// Variable?
		if(substr($owner_id,0,4) == 'var_') {
			@$owner_id = intval($dict->$owner_id);
		}
		
		$fields = array(
			DAO_Ticket::OWNER_ID => $owner_id,
		);
		DAO_Ticket::update($ticket_id, $fields);
		
		/**
		 * Re-update owner values
		 */
		// [TODO] Redo with DevblocksDictionaryDelegate
		/*
		$worker_labels = array();
		$worker_values = array();
		$labels = array();
		CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $owner_id, $worker_labels, $worker_values, NULL, true);
				
			// Clear dupe content
			CerberusContexts::scrubTokensWithRegexp(
				$worker_labels,
				$worker_values,
				array(
					"#^address_org_#",
				)
			);
		
			// Merge
			CerberusContexts::merge(
				$values_prefix,
				'',
				$worker_labels,
				$worker_values,
				$labels,
				$values
			);
		*/
	}
	
	static function renderActionAddWatchers($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$event = $trigger->getEvent();
		$tpl->assign('values_to_contexts', $event->getValuesContexts($trigger));
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_add_watchers.tpl');
	}

	static function simulateActionAddWatchers($params, DevblocksDictionaryDelegate $dict, $default_on) {
		@$worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$worker_ids = DevblocksEventHelper::mergeWorkerVars($worker_ids, $dict);

		// Event
		
		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		// Watchers
		
		$out = ">>> Adding watchers:\n";
		
		if(!is_array($worker_ids) || empty($worker_ids)) {
			$out .= " * [ERROR] No watchers are being set. Skipping...";
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
				$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
				$out .= ' * (' . $on_object_context->manifest->name . ') ' . @$on_object['_label'] . "\n";  
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
		
		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		// On: Are we watching something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				CerberusContexts::addWatchers($on_object['_context'], $on_object['id'], $worker_ids);
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

		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		$out = sprintf(">>> Sending a notification:\n".
			"\n".
			"%s\n".
			"\n".
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
				$out .= ">>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object['_label'] . "\n";  
				}
				
				$out .= "\n";
			}
		}		
		
		// Notify
		
		$notify_worker_ids = isset($params['notify_worker_id']) ? $params['notify_worker_id'] : array();
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);

		if(!empty($notify_worker_ids)) {
			$out .= ">>> Notifying:\n";
			
			foreach($notify_worker_ids as $worker_id) {
				if(null != ($worker = DAO_Worker::get($worker_id))) {
					$out .= " * " . $worker->getName() . "\n";
				}
			}
		}
		
		return $out;		
	}
	
	static function runActionCreateNotification($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$trigger = $dict->_trigger;
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
		
		$notify_contexts = array();
		
		// On: Are we notifying about something else?
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
		@$on_objects = $on_result['objects'];
		
		if(is_array($on_objects)) {
			foreach($on_objects as $on_object) {
				$notify_contexts[] = array($on_object['_context'], $on_object['id']);
			}
		}
			
		// Send notifications
		
		foreach($notify_worker_ids as $notify_worker_id) {
			foreach($notify_contexts as $notify_context_data) {
				$fields = array(
					DAO_Notification::CONTEXT => $notify_context_data[0],
					DAO_Notification::CONTEXT_ID => $notify_context_data[1],
					DAO_Notification::WORKER_ID => $notify_worker_id,
					DAO_Notification::CREATED_DATE => time(),
					DAO_Notification::MESSAGE => $content,
					DAO_Notification::URL => '',
				);
				$notification_id = DAO_Notification::create($fields);
			}
			
			DAO_Notification::clearCountCache($notify_worker_id);
		}
		
		return $notification_id;
	}
	
	/*
	 * Action: Create Task
	 */
	
	static function renderActionCreateTask($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_task.tpl');
	}
	
	static function simulateActionCreateTask($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$due_date = $params['due_date'];

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$title = $tpl_builder->build($params['title'], $dict);
		$due_date = intval(@strtotime($tpl_builder->build($params['due_date'], $dict)));
		$comment = $tpl_builder->build($params['comment'], $dict);

		$out = sprintf(">>> Creating task\n".
			"Title: %s\n".
			"Due Date: %s (%s)\n".
			"\n".
			"",
			$title,
			(!empty($due_date) ? date("Y-m-d h:ia", $due_date) : 'none'),
			$params['due_date']
		);
		
		// On

		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		if(!empty($on)) {
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= ">>> On:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object['_context']);;
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object['_label'] . "\n";  
				}
				$out .= "\n";
			}
		}		
		
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
		
		// Connection
		if(!empty($context) && !empty($context_id)) {
			if(null != ($ctx = Extension_DevblocksContext::get($context, true))) {
				$meta = $ctx->getMeta($context_id);
				$out .= ">>> Linking new task to:\n";
				$out .= ' * (' . $ctx->manifest->name . ') ' . $meta['name'] . "\n";
				$out .= "\n";
			}
		}

		return $out;
	}
	
	static function runActionCreateTask($params, DevblocksDictionaryDelegate $dict, $default_on) {
		$due_date = $params['due_date'];

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
				
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$title = $tpl_builder->build($params['title'], $dict);
		$due_date = intval(@strtotime($tpl_builder->build($params['due_date'], $dict)));
		$comment = $tpl_builder->build($params['comment'], $dict);

		// On

		$trigger = $dict->_trigger;
		$event = $trigger->getEvent();
		
		@$on = DevblocksPlatform::importVar($params['on'],'string',$default_on);
		
		if(!empty($on)) {
			$on_result = DevblocksEventHelper::onContexts($on, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					$fields = array(
						DAO_Task::TITLE => $title,
						DAO_Task::UPDATED_DATE => time(),
						DAO_Task::DUE_DATE => $due_date,
					);
					$task_id = DAO_Task::create($fields);
			
					// Watchers
					if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
						CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TASK, $task_id, $watcher_worker_ids);
					}
					
					// Comment content
					if(!empty($comment)) {
						$fields = array(
							DAO_Comment::ADDRESS_ID => 0,
							DAO_Comment::COMMENT => $comment,
							DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TASK,
							DAO_Comment::CONTEXT_ID => $task_id,
							DAO_Comment::CREATED => time(),
						);
						DAO_Comment::create($fields, $notify_worker_ids);
					}
					
					// Connection
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TASK, $task_id, $on_object['_context'], $on_object['id']);
				}
			}
		}

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
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_create_ticket.tpl');
	}
	
	static function simulateActionCreateTicket($params, DevblocksDictionaryDelegate $dict) {
		@$group_id = $params['group_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$group_replyto = $group->getReplyTo();

		@$watcher_worker_ids = DevblocksPlatform::importVar($params['worker_id'],'array',array());
		$watcher_worker_ids = DevblocksEventHelper::mergeWorkerVars($watcher_worker_ids, $dict);
		
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		$requesters = $tpl_builder->build($params['requesters'], $dict);
		$subject = $tpl_builder->build($params['subject'], $dict);
		$content = $tpl_builder->build($params['content'], $dict);
		
		$out = sprintf(">>> Creating ticket\n".
			"Group: %s <%s>\n".
			"Requesters: %s\n".
			"Subject: %s\n".
			"\n".
			"%s\n".
			"\n".
			"",
			$group->name,
			$group_replyto->email,
			$requesters,
			$subject,
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
		@$link_to = DevblocksPlatform::importVar($params['link_to'],'array',array());
		
		if(!empty($link_to)) {
			$trigger = $dict->_trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($link_to, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				$out .= ">>> Linking new ticket to:\n";
				
				foreach($on_objects as $on_object) {
					$on_object_context = Extension_DevblocksContext::get($on_object['_context']);
					$out .= ' * (' . $on_object_context->manifest->name . ') ' . $on_object['_label'] . "\n";
				}
			}
			
			$out .= "\n";
		}
		
		return $out;
	}
	
	static function runActionCreateTicket($params, DevblocksDictionaryDelegate $dict) {
		@$group_id = $params['group_id'];
		
		if(null == ($group = DAO_Group::get($group_id)))
			return;
		
		$group_replyto = $group->getReplyTo();
			
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

		$message->body = sprintf(
			"(... This message was manually created by a virtual attendant on behalf of the requesters ...)\r\n"
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
		
		// Watchers
		if(is_array($watcher_worker_ids) && !empty($watcher_worker_ids)) {
			CerberusContexts::addWatchers(CerberusContexts::CONTEXT_TICKET, $ticket_id, $watcher_worker_ids);
		}
		
		CerberusMail::sendTicketMessage($properties);
		
		// Connection
		
		@$link_to = DevblocksPlatform::importVar($params['link_to'],'array',array());
		
		if(!empty($link_to)) {
			$trigger = $dict->_trigger;
			$event = $trigger->getEvent();
			
			$on_result = DevblocksEventHelper::onContexts($link_to, $event->getValuesContexts($trigger), $dict);
			@$on_objects = $on_result['objects'];
			
			if(is_array($on_objects)) {
				foreach($on_objects as $on_object) {
					DAO_ContextLink::setLink(CerberusContexts::CONTEXT_TICKET, $ticket_id, $on_object['_context'], $on_object['id']);
				}
			}
		}
		
		return $ticket_id;
	}
	
	/*
	 * Action: Send Email
	 */
	
	function renderActionSendEmail($trigger) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_send_email.tpl');
	}
	
	function simulateActionSendEmail($params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		@$trigger = $dict->_trigger;
		@$to_vars = @$params['to_var'];
		$to = array();
		
		if(isset($params['to']) && !empty($params['to'])) {
			if(false == ($to_string = $tpl_builder->build($params['to'], $dict)))
				return "[ERROR] The 'to' field has invalid placeholders.";
			
			$to = DevblocksPlatform::parseCsvString($to_string);
		}
		
		if(is_array($to_vars))
		foreach($to_vars as $to_var) {
			if(!isset($dict->$to_var))
				continue;
			
			// Security check
			if(substr($to_var,0,4) != 'var_')
				continue;
			
			$address_ids = $dict->$to_var;
			
			$addresses = DAO_Address::getWhere(sprintf("%s IN (%s)",
				DAO_Address::ID,
				implode(",", $address_ids)
			));
			
			if(is_array($addresses))
			foreach($addresses as $addy) { /* @var $addy Model_Address */
				$to[] = $addy->email;
			}
		}
		
		if(empty($to)) {
			return "[ERROR] The 'to' field has no recipients.";
		}
		
		$to = array_unique($to);
		
		if(false == ($subject = $tpl_builder->build($params['subject'], $dict))) {
			return "[ERROR] The 'subject' field has invalid placeholders.";
		}
		
		if(false == ($content = $tpl_builder->build($params['content'], $dict))) {
			return "[ERROR] The 'content' field has invalid placeholders.";
		}
		
		// [TODO] Simulate 'From:'
		
		$out = sprintf(">>> Sending email\n".
			"To: %s\n".
			"Subject: %s\n".
			"\n".
			"%s\n",
			implode(",\n  ", $to),
			$subject,
			$content
		);
		
		return $out;
	}
	
	function runActionSendEmail($params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$trigger = $dict->_trigger;
		@$to_vars = @$params['to_var'];
		$to = array();
		
		// To
		
		if(isset($params['to']) && !empty($params['to'])) {
			if(false !== ($to_string = $tpl_builder->build($params['to'], $dict)))
				$to = DevblocksPlatform::parseCsvString($to_string);
		}
		
		if(is_array($to_vars))
		foreach($to_vars as $to_var) {
			if(!isset($dict->$to_var))
				continue;
			
			// Security check
			if(substr($to_var,0,4) != 'var_')
				continue;
			
			$address_ids = $dict->$to_var;
			
			$addresses = DAO_Address::getWhere(sprintf("%s IN (%s)",
				DAO_Address::ID,
				implode(",", $address_ids)
			));
			
			if(is_array($addresses))
			foreach($addresses as $addy) { /* @var $addy Model_Address */
				$to[] = $addy->email;
			}
		}

		// Properties 
		
		$subject = $tpl_builder->build($params['subject'], $dict);
		$content = $tpl_builder->build($params['content'], $dict);

		CerberusMail::quickSend(
			implode(', ', $to),
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
	
	function runActionRelayEmail($params, DevblocksDictionaryDelegate $dict, $context, $context_id, $group_id, $bucket_id, $message_id, $owner_id, $sender_email, $sender_name, $subject) {
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
					$subject = $tpl_builder->build($params['subject'], $dict);
					$mail->setSubject($subject);
				}
	
				// Find the owner of this address and sign it.
				$sign = substr(md5($context.$context_id.$worker->pass),8,8);
				
				$headers->removeAll('message-id');
				$headers->addTextHeader('Message-Id', sprintf("<%s_%d_%d_%s@cerb5>", $context, $context_id, time(), $sign));
				$headers->addTextHeader('X-CerberusRedirect','1');
	
				$content = $tpl_builder->build($params['content'], $dict);
				
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
	
	static function onContexts($on_keys, $values_to_contexts, DevblocksDictionaryDelegate $dict) {
		$result = array();
		
		if(!empty($on_keys)) {
			if(!is_array($on_keys))
				$on_keys = array($on_keys);

			$vals = array();
			
			foreach($on_keys as $on) {
				@$on_value = $dict->$on;
				
				if(preg_match("#(.*)_watchers#", $on)) {
					if(is_array($on_value))
						$vals = $on_value;
					
				} else {
					if(!is_null($on))
						$vals = is_array($on_value) ? $on_value : array($on_value);
				}
				
				@$ctx_ext = $values_to_contexts[$on]['context'];
				foreach($vals as $ctx_id => $ctx_object) {
					if(!is_array($ctx_object)) {
						$ctx_id = $ctx_object;
						CerberusContexts::getContext($ctx_ext, $ctx_id, $null, $ctx_object);
					}
					
					$result['objects'][$ctx_id] = $ctx_object;
				}
			}
		}
		
		return $result;
	}
	
	static function mergeWorkerVars($worker_ids, DevblocksDictionaryDelegate $dict) {
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
		
		return array_unique($worker_ids);
	}
	
	static function getParamsViewModel($token, $params, $trigger, $context) {
		$view_model = null;
		
		if(isset($params['view_model'])) {
			$view_model_encoded = $params['view_model'];
			$view_model = unserialize(base64_decode($view_model_encoded));
		}
		
		if(empty($view_model)) {
			$view_id = sprintf("_trigger_%d_%s_%s",
				$trigger->id,
				$token,
				uniqid()
			);
			
			$ctx = Extension_DevblocksContext::get($context);
			
			$view = $ctx->getChooserView(); /* @var $view C4_AbstractView */
			
			if($view instanceof C4_AbstractView) {
				$view->id = $view_id;
				$view->is_ephemeral = true;
				$view->renderFilters = true;
	
				$view_model = C4_AbstractViewLoader::serializeAbstractView($view);
			}
		}
		
		return $view_model;
	}
	
	static function runActionSetListVariable($token, $context, $params, DevblocksDictionaryDelegate $dict) {
		$trigger = $dict->_trigger;
		
		if(null == ($view_model = DevblocksEventHelper::getParamsViewModel($token, $params, $trigger, $context)))
			return;
		
		// Force reload parameters (we can't trust the session)
		if(false == ($view = C4_AbstractViewLoader::unserializeAbstractView($view_model)))
			return;
		
		$view->setPlaceholderValues($dict->getDictionary());
		
		// [TODO] Iterate through pages if over a certain list length?
		//$view->renderLimit = (isset($params['limit']) && is_numeric($params['limit'])) ? intval($params['limit']) : 100;
		$view->renderLimit = 100;
		$view->renderPage = 0;
		$view->renderTotal = false;
		
		list($results) = $view->getData();
		
		if(!isset($dict->$token) || !is_array($dict->$token))
			$dict->$token = array();

		$old_ids = array_keys($dict->$token);
		$new_ids = array_keys($results);

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
				$array = $dict->$token;
				$array[$new_id] = $obj_values;
				$dict->$token = $array;
			}
		}
	}
};