<?php
class CustomField_GeoPoint extends Extension_CustomField {
	const ID = 'cerb.custom_field.geo.point';
	
	function renderEditable(Model_CustomField $field, $form_key, $form_value) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('field', $field);
		$tpl->assign('form_key', $form_key);
		
		$form_value = $this->getValue($form_value);
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
	
	function getValue($value) {
		if(false === ($coords = DevblocksPlatform::parseGeoPointString($value)))
			return FALSE;
		
		return sprintf('%f, %f',
			$coords['latitude'],
			$coords['longitude']
		);
	}
	
	function renderValue($value) {
		$value = $this->getValue($value);
		echo htmlentities($value);
	}
	
	function getLabelsForValues($values) {
		$map = $values;
		
		foreach($values as $v) {
			$map[$v] = $this->getValue($v);
		}
		
		return $map;
	}
	
	function prepareCriteriaParam(Model_CustomField $field, $param, &$vals, &$implode_token) {
		$implode_token = ', ';
		return true;
	}

	function populateQuickSearchMeta(array &$search_field_meta) {
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
};