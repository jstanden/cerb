<?php
class CustomField_GeoPoint extends Extension_CustomField {
	const ID = 'cerb.custom_field.geo.point';
	
	function renderConfig(Model_CustomField $field) {
	}
	
	function renderEditable(Model_CustomField $field, $form_key, $form_value) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);
		$tpl->assign('form_key', $form_key);
		
		$form_value = $this->getValue($field, $form_value);
		$tpl->assign('form_value', $form_value);
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/geopoint/editor.tpl');
	}
	
	function validationRegister(Model_CustomField $field, _DevblocksValidationService &$validation) {
		$validation
			->addField($field->id, $field->name)
			->geopoint()
		;
	}
	
	function formatFieldValue($value) {
		return $value;
	}
	
	function setFieldValue(Model_CustomField $field, $context, $context_id, $value) {
		$db = DevblocksPlatform::services()->database();
		
		// Verify the value is a point
		if(false === ($coords = DevblocksPlatform::parseGeoPointString($value)))
			return FALSE;
		
		self::unsetFieldValue($field, $context, $context_id, $value);
		
		$sql = sprintf("INSERT INTO custom_field_geovalue (field_id, context, context_id, field_value) ".
			"VALUES (%d, %s, %d, POINT(%f,%f))",
			$field->id,
			$db->qstr($context),
			$context_id,
			$coords['longitude'],
			$coords['latitude']
		);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function unsetFieldValue(Model_CustomField $field, $context, $context_id, $value=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM custom_field_geovalue WHERE field_id = %d AND context = %s AND context_id = %d",
			$field->id,
			$db->qstr($context),
			$context_id
		);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function getValue(Model_CustomField $field, $value) {
		if(false === ($coords = DevblocksPlatform::parseGeoPointString($value)))
			return FALSE;
		
		return sprintf('%f, %f',
			$coords['latitude'],
			$coords['longitude']
		);
	}
	
	function getDictionaryValues(Model_CustomField $field, $value, $as_keys=true, &$token_values=[]) {
		$value = $this->getValue($field, $value);
		
		if($as_keys) {
			$token_values[$field->uri] = $value;
		} else {
			$token_values['custom'][$field->id] = $value;
			$token_values['custom_' . $field->id] = $value;
		}
	}
	
	function getValuesContexts(Model_CustomField $field, $token, &$values) {
		return;
	}
	
	function getVarValueToContextMap(Model_TriggerEvent $trigger, string $var_key, $var, &$values_to_contexts) {
		return;
	}
	
	function renderValue(Model_CustomField $field, $value) {
		$value = $this->getValue($field, $value);
		echo htmlentities($value);
	}
	
	function getLabelsForValues(Model_CustomField $field, $values) {
		$map = $values;
		
		foreach($values as $v) {
			$map[$v] = $this->getValue($field, $v);
		}
		
		return $map;
	}
	
	function prepareCriteriaParam(Model_CustomField $field, $param, &$vals, &$implode_token) {
		$implode_token = ', ';
		return true;
	}

	function populateQuickSearchMeta(Model_CustomField $field, array &$search_field_meta) {
		$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_GEO_POINT;
		return true;
	}
	
	function getValueTableName() {
		return 'custom_field_geovalue';
	}
	
	function getValueTableSql($context, array $context_ids) {
		return sprintf("SELECT context_id, field_id, ST_ASTEXT(field_value) AS field_value ".
			"FROM custom_field_geovalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
	}
	
	function botActionRender(Model_CustomField $field) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('instructions', '(a pair of comma-separated latitude/longitude coordinates; e.g. `44.787197, 20.457273`)');
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
	}
	
	function botActionSimulate(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $value_key) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$out = ">>> Setting value to:\n";
		
		$value = $params['value'] ?? null;
		$value = $tpl_builder->build($value, $dict);

		$value = $this->getValue($field, $value);
		
		$out .= $value;
		
		$dict->set($value_key, $value);
		
		return $out;
	}
	
	function botActionRun(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $context, $context_id, $value_key) {
		$value = $this->botActionGetValueFromParams($field, $params, $dict);
		
		$this->setFieldValue($field, $context, $context_id, $value);
		
		if(!empty($value_key)) {
			$key_to_set = $value_key.'_'.$field->id;
			$dict->set($key_to_set, $value);
			
			$array =& $dict->$value_key;
			if(is_array($array))
				$array[$field->id] = $value;
		}
	}
	
	function botActionGetValueFromParams(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict) {
		$value = $params['value'] ?? null;
		return $this->formatFieldValue($value);
	}
};

class CustomField_Slider extends Extension_CustomField {
	const ID = 'cerb.custom_field.slider';
	
	function renderConfig(Model_CustomField $field) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/slider/config.tpl');
	}
	
	function renderEditable(Model_CustomField $field, $form_key, $form_value) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);
		$tpl->assign('form_key', $form_key);
		$tpl->assign('form_value', $this->getValue($field, $form_value));
		
		$value_min = $field->params['value_min'] ?? null;
		$value_max = $field->params['value_max'] ?? null;
		
		if(0 == strlen($value_min))
			$value_min = 0;
		
		if(0 == strlen($value_max))
			$value_max = 100;
		
		$tpl->assign('value_min', $value_min);
		$tpl->assign('value_max', $value_max);
		
		$value_range = $value_max - $value_min;
		
		$tpl->assign('value_mid', ($value_range/2)+$value_min);
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/slider/editor.tpl');
	}
	
	function validationRegister(Model_CustomField $field, _DevblocksValidationService &$validation) {
		$field_context = $field->params['context'] ?? null;
		
		$value_min = $field->params['value_min'] ?? null;
		$value_max = $field->params['value_max'] ?? null;
		
		if(0 == strlen($value_min))
			$value_min = 0;
		
		if(0 == strlen($value_max))
			$value_max = 100;
		
		$validation
			->addField($field->id, $field->name)
			->number()
			->setMin(intval($value_min))
			->setMax(intval($value_max))
		;
	}
	
	function formatFieldValue($value) {
		return $value;
	}
	
	function parseFormPost(Model_CustomField $field) {
		@$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field->id],'integer',0);
		return $field_value;
	}
	
	function setFieldValue(Model_CustomField $field, $context, $context_id, $value) {
		$db = DevblocksPlatform::services()->database();
		
		self::unsetFieldValue($field, $context, $context_id, $value);
		
		$sql = sprintf("INSERT INTO custom_field_numbervalue (field_id, context, context_id, field_value) ".
			"VALUES (%d, %s, %d, %d)",
			$field->id,
			$db->qstr($context),
			$context_id,
			$value
		);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function unsetFieldValue(Model_CustomField $field, $context, $context_id, $value=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM custom_field_numbervalue WHERE field_id = %d AND context = %s AND context_id = %d",
			$field->id,
			$db->qstr($context),
			$context_id
		);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function hasMultipleValues() {
		return false;
	}
	
	function getValue(Model_CustomField $field, $value) {
		return $value;
	}
	
	function getDictionaryValues(Model_CustomField $field, $value, $as_keys=true, &$token_values=[]) {
		$value = $this->getValue($field, $value);
		
		if($as_keys) {
			$token_values[$field->uri] = $value;
		} else {
			$token_values['custom'][$field->id] = $value;
			$token_values['custom_' . $field->id] = $value;
		}
	}
	
	function getValuesContexts(Model_CustomField $field, $token, &$values) {
		return;
	}
	
	function getVarValueToContextMap(Model_TriggerEvent $trigger, string $var_key, $var, &$values_to_contexts) {
		return;
	}
	
	function renderValue(Model_CustomField $field, $value) {
		$tpl = DevblocksPlatform::services()->template();
		
		$value_min = $field->params['value_min'] ?? null;
		$value_max = $field->params['value_max'] ?? null;
		
		if(0 == strlen($value_min))
			$value_min = 0;
		
		if(0 == strlen($value_max))
			$value_max = 100;
		
		$tpl->assign('value_min', $value_min);
		$tpl->assign('value_max', $value_max);
		
		$value_range = $value_max - $value_min;
		
		$tpl->assign('value_percent', 100 * (($value - $value_min) / $value_range));
		
		$tpl->assign('value', $value);
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/slider/render_value.tpl');
	}
	
	function getLabelsForValues(Model_CustomField $field, $values) {
		$map = $values;
		
		foreach($values as $v) {
			$map[$v] = $this->getValue($field, $v);
		}
		
		return $map;
	}
	
	function prepareCriteriaParam(Model_CustomField $field, $param, &$vals, &$implode_token) {
		$implode_token = ', ';
		return true;
	}
	
	function populateQuickSearchMeta(Model_CustomField $field, array &$search_field_meta) {
		$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_NUMBER;
		return true;
	}
	
	function getParamFromQueryFieldTokens($field, $tokens, $param_key) {
		if($param_key && false != $param = DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, $param_key))
			return $param;
		
		return null;
	}
	
	function getValueTableName() {
		return 'custom_field_numbervalue';
	}
	
	function getValueTableSql($context, array $context_ids) {
		return sprintf("SELECT context_id, field_id, field_value ".
			"FROM custom_field_numbervalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
	}
	
	function botActionRender(Model_CustomField $field) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('instructions',
			sprintf('(return a number between %d and %d)',
				$field->params['value_min'],
				$field->params['value_max']
			)
		);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
	}
	
	function botActionSimulate(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $value_key) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$out = ">>> Setting value to:\n";
		
		$value = $params['value'] ?? null;
		$value = $tpl_builder->build($value, $dict);
		
		$value = $this->getValue($field, $value);
		
		$out .= $value;
		
		$dict->set($value_key, $value);
		
		return $out;
	}
	
	function botActionRun(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $context, $context_id, $value_key) {
		$value = $this->botActionGetValueFromParams($field, $params, $dict);
		
		$this->setFieldValue($field, $context, $context_id, $value);
		
		if(!empty($value_key)) {
			$key_to_set = $value_key.'_'.$field->id;
			$dict->set($key_to_set, $value);
			
			$array =& $dict->$value_key;
			if(is_array($array))
				$array[$field->id] = $value;
		}
	}
	
	function botActionGetValueFromParams(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict) {
		$value = $params['value'] ?? null;
		return $this->formatFieldValue($value);
	}
};

class CustomField_RecordLinks extends Extension_CustomField {
	const ID = 'cerb.custom_field.record.links';
	
	function renderConfig(Model_CustomField $field) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);

		$contexts = Extension_DevblocksContext::getAll(false);
		$tpl->assign('contexts', $contexts);
		
		// [TODO] This can specify a default query for filtering records (e.g. tag types)

		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/record_links/config.tpl');
	}
	
	function renderEditable(Model_CustomField $field, $form_key, $form_value) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);
		$tpl->assign('form_key', $form_key);
		
		$form_value = $this->getValue($field, $form_value);
		
		$linked_context = $field->params['context'] ?? null;
		$linked_dicts = [];
		
		if($linked_context && is_array($form_value)) {
			$models = CerberusContexts::getModels($linked_context, $form_value);
			$linked_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $linked_context, ['_label']);
		}
		
		$tpl->assign('linked_dicts', $linked_dicts);
		
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/record_links/editor.tpl');
	}
	
	function validationRegister(Model_CustomField $field, _DevblocksValidationService &$validation) {
		$field_context = $field->params['context'] ?? null;
		
		$validation
			->addField($field->id, $field->name)
			->idArray()
			->addValidator($validation->validators()->contextIds($field_context, true))
			;
	}
	
	function formatFieldValue($value) {
		if (is_array($value)) {
			return $value;
		} else if(is_string($value)) {
			return DevblocksPlatform::parseCsvString($value);
		} else {
			return [];
		}
	}
	
	function parseFormPost(Model_CustomField $field) {
		$field_value = DevblocksPlatform::importGPC($_POST['field_'.$field->id] ?? null,'array',[]);
		return DevblocksPlatform::sanitizeArray($field_value, 'int', ['nonzero', 'unique']);
	}
	
	function setFieldValue(Model_CustomField $field, $context, $context_id, $value) {
		$db = DevblocksPlatform::services()->database();
		
		if(!is_array($value))
			return FALSE;
		
		$value = DevblocksPlatform::sanitizeArray($value, 'int');
		
		self::unsetFieldValue($field, $context, $context_id);
		
		$values = [];
		
		foreach($value as $v) {
			$values[] = sprintf("(%d, %s, %d, %d)",
				$field->id,
				$db->qstr($context),
				$context_id,
				$v
			);
		}
		
		if($values) {
			$sql = sprintf("INSERT INTO custom_field_numbervalue (field_id, context, context_id, field_value) VALUES %s",
				implode(',', $values)
			);
			$db->ExecuteMaster($sql);
		}
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function unsetFieldValue(Model_CustomField $field, $context, $context_id, $value=null) {
		$db = DevblocksPlatform::services()->database();
		
		$sql = sprintf("DELETE FROM custom_field_numbervalue WHERE field_id = %d AND context = %s AND context_id = %d",
			$field->id,
			$db->qstr($context),
			$context_id
		);
		$db->ExecuteMaster($sql);
		
		DevblocksPlatform::markContextChanged($context, $context_id);
	}
	
	function hasMultipleValues() {
		return true;
	}
	
	function getValue(Model_CustomField $field, $value) {
		return $value;
	}
	
	function getDictionaryValues(Model_CustomField $field, $value, $as_keys=true, &$token_values=[]) {
		$value = $this->getValue($field, $value);
		
		if($as_keys) {
			$token_values[$field->uri . '_records'] = [];
			
			if(is_array($value)) {
				foreach ($value as $v) {
					$token_values[$field->uri . '_records'][$v] = DevblocksDictionaryDelegate::instance([
						'_context' => $field->params['context'],
						'id' => $v,
					]);
				}
			}
			
		} else {
			// Deprecated
			$token_values['custom'][$field->id] = $value;
			$token_values['custom_' . $field->id] = $value;
			
			if(is_array($value)) {
				foreach($value as $v) {
					$token_values['custom_' . $field->id . '_records'][$v] = DevblocksDictionaryDelegate::instance([
						'_context' => $field->params['context'],
						'id' => $v,
					]);
				}
			}
		}
	}
	
	function getValuesContexts(Model_CustomField $field, $token, &$values) {
		$values[$token] = [
			'label' => '',
			'context' => $field->params['context'],
		];
	}
	
	function getVarValueToContextMap(Model_TriggerEvent $trigger, string $var_key, $var, &$values_to_contexts) {
		$values_to_contexts[$var_key] = [
			'label' => '(variable) ' . $var['label'],
			'context' => $var['params']['context'],
		];
		return;
	}
	
	// [TODO] This should be more efficient on worklists (once per page, lots of dupes in cols)
	function renderValue(Model_CustomField $field, $value) {
		if(is_string($value)) {
			$values = DevblocksPlatform::parseCsvString($value);
		} else if (is_array($value)) {
			$values = $value;
		} else {
			return;
		}
		
		$tpl = DevblocksPlatform::services()->template();
		
		$target_context = $field->params['context'] ?? null;
		
		$models = CerberusContexts::getModels($target_context, $values);
		$target_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $target_context, ['_label']);
		
		$tpl->assign('target_dicts', $target_dicts);
		$tpl->display('devblocks:cerberusweb.core::internal/custom_fields/extensions/record_links/render_value.tpl');
	}
	
	function getLabelsForValues(Model_CustomField $field, $values) {
		$map = $values;
		
		// [TODO] Get labels from records
		
		foreach($values as $v) {
			$map[$v] = $this->getValue($field, $v);
		}
		
		return $map;
	}
	
	function prepareCriteriaParam(Model_CustomField $field, $param, &$vals, &$implode_token) {
		$implode_token = ', ';
		return true;
	}
	
	function populateQuickSearchMeta(Model_CustomField $field, array &$search_field_meta) {
		$search_field_meta['type'] = DevblocksSearchCriteria::TYPE_VIRTUAL;
		
		// [TODO] query config
		$search_field_meta['examples'][] = [
			'type' => 'search',
			'context' => $field->params['context'] ?? null,
			'q' => $field->params['query'] ?? null,
		];
		
		return true;
	}

	function getParamFromQueryFieldTokens($field, $tokens, $param_key) {
		if($param_key && false != $param = DevblocksSearchCriteria::getVirtualQuickSearchParamFromTokens($field, $tokens, $param_key))
			return $param;
		
		return null;
	}

	function getWhereSQLFromParam(Model_CustomField $field, DevblocksSearchCriteria $param) {
		switch($param->operator) {
			case DevblocksSearchCriteria::OPER_CUSTOM:
				$links_context = $field->params['context'] ?? null;
				
				if(false == ($context_ext = Extension_DevblocksContext::get($field->context, true)))
					return null;
				
				if(false == ($search_class = $context_ext->getSearchClass()))
					return null;
				
				if(false == ($cfield_key = $search_class::getCustomFieldContextWhereKey($field->context)))
					return null;
				
				/** @noinspection SqlResolve */
				$subquery_sql = sprintf("SELECT context_id FROM %s WHERE field_id = %d AND field_value IN (%s)",
					$this->getValueTableName(),
					$field->id,
					'%s'
				);
				
				$where_sql = $search_class::_getWhereSQLFromVirtualSearchSqlField(
					$param,
					$links_context,
					$subquery_sql,
					$cfield_key
				);
				
				return $where_sql;
				break;
		}
	}
	
	function getValueTableName() {
		return 'custom_field_numbervalue';
	}
	
	function getValueTableSql($context, array $context_ids) {
		return sprintf("SELECT context_id, field_id, field_value ".
			"FROM custom_field_numbervalue ".
			"WHERE context = '%s' AND context_id IN (%s)",
			$context,
			implode(',', $context_ids)
		);
	}
	
	function botActionRender(Model_CustomField $field) {
		$tpl = DevblocksPlatform::services()->template();
		
		if(!array_key_exists('context', $field->params))
			return;
		
		if(false == ($context_mft = Extension_DevblocksContext::get($field->params['context'], false)))
			return;
		
		$aliases = Extension_DevblocksContext::getAliasesForContext($context_mft);
		
		$tpl->assign('instructions',
			sprintf('(return comma-separated %s ids)',
				DevblocksPlatform::strLower($aliases['plural'])
			)
		);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_set_var_string.tpl');
	}
	
	function botActionSimulate(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $value_key) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$out = ">>> Setting value to:\n";
		
		$value = $params['value'] ?? null;
		$value = $tpl_builder->build($value, $dict);
		
		$value = $this->getValue($field, $value);
		
		$out .= $value;
		
		$dict->set($value_key, $value);
		
		return $out;
	}
	
	function botActionRun(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict, $context, $context_id, $value_key) {
		$value = $this->botActionGetValueFromParams($field, $params, $dict);
		
		$this->setFieldValue($field, $context, $context_id, $value);
		
		if(!empty($value_key)) {
			$key_to_set = $value_key.'_'.$field->id;
			$dict->set($key_to_set, $value);
			
			$array =& $dict->$value_key;
			if(is_array($array))
				$array[$field->id] = $value;
		}
	}
	
	function botActionGetValueFromParams(Model_CustomField $field, array $params, DevblocksDictionaryDelegate $dict) {
		$value = $params['value'] ?? null;
		return $this->formatFieldValue($value);
	}
};