<?php
namespace Cerb\Records;

use CerberusContexts;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use Extension_DevblocksContext;
use IDevblocksContextProfile;
use Model_CustomField;
use Page_Profiles;

/**
 * Builds the WYSIWYG field preview shown in the config UI of the workspace, card, and profile
 * "Fields" widgets. Picks a representative record of the chosen context, assembles its available
 * base + custom fields and fieldsets with real (or sample) values, and returns everything the
 * config template needs to render every selectable field.
 */
class RecordFieldsPreview {
	static function build(Extension_DevblocksContext $context_ext, ?array $selected_groups=null) : array {
		$context = $context_ext->id;

		// A representative record of this type (null if none exist yet)
		$record = null;

		if(($record_id = $context_ext->getRandom())) {
			$dao_class = $context_ext->getDaoClass();

			if($dao_class && method_exists($dao_class, 'get'))
				$record = $dao_class::get($record_id);
		}

		// Real custom-field values for that record (empty if none)
		$values = [];

		if($record) {
			$values = DAO_CustomFieldValue::getValuesByContextIds($context, $record->id);
			$values = is_array($values) ? (array)array_shift($values) : [];
		}

		// All available base + standalone custom fields, with real values
		$properties = $context_ext instanceof IDevblocksContextProfile ? $context_ext->profileGetFields($record) : [];

		$properties_cfields = Page_Profiles::getProfilePropertiesCustomFields($context, $values);

		if(!empty($properties_cfields))
			$properties = array_merge($properties, $properties_cfields);

		// All custom fieldsets, with real values
		$custom_fieldsets = Page_Profiles::getProfilePropertiesCustomFieldsets($context, $record?->id, $values, true);

		// Fill empty fields with a type dummy so the preview doesn't look blank (relational types are
		// left empty on purpose — a fake id wouldn't render — and the template shows a placeholder)
		self::_fillSampleValues($properties);

		foreach($custom_fieldsets as &$fieldset) {
			if(is_array($fieldset['properties'] ?? null))
				self::_fillSampleValues($fieldset['properties']);
		}
		unset($fieldset);

		// Sample dictionary (the cell renderer's `phone` branch reads $dict)
		$dict = null;

		if($record) {
			$labels = $token_values = [];
			CerberusContexts::getContext($context, $record, $labels, $token_values, '', true, false);
			$dict = DevblocksDictionaryDelegate::instance($token_values);
		}

		return [
			'properties' => $properties,
			'custom_fieldsets' => $custom_fieldsets,
			'custom_field_values' => $values,
			'dict' => $dict,
			'selected' => $selected_groups ?: [],
		];
	}

	// Give empty scalar fields a representative value so the preview reads as populated. Relational
	// types are left untouched (the template renders a muted placeholder for them instead).
	private static function _fillSampleValues(array &$properties) : void {
		foreach($properties as &$property) {
			if(!empty($property['value']))
				continue;

			$dummy = self::getSampleValueForType($property['type'] ?? '', $property['params'] ?? []);

			if(!is_null($dummy))
				$property['value'] = $dummy;
		}
		unset($property);
	}

	// A representative placeholder value for a field type (null = relational/unknown → no dummy)
	static function getSampleValueForType($type, array $params=[]) {
		switch($type) {
			case Model_CustomField::TYPE_DATE:
				return time();
			case Model_CustomField::TYPE_CHECKBOX:
				return 1;
			case Model_CustomField::TYPE_NUMBER:
				return 1234;
			case Model_CustomField::TYPE_DECIMAL:
				return 12.34;
			case Model_CustomField::TYPE_CURRENCY:
				return 1234.56;
			case Model_CustomField::TYPE_SINGLE_LINE:
				return 'Sample text';
			case Model_CustomField::TYPE_MULTI_LINE:
				return 'Sample text for this field.';
			case Model_CustomField::TYPE_URL:
				return 'https://example.com';
			case Model_CustomField::TYPE_LIST:
			case Model_CustomField::TYPE_MULTI_CHECKBOX:
				return ['Sample', 'Example'];
			case 'slider':
				return intval($params['mid'] ?? 50);
			case 'percent':
				return 0.42;
			case 'size_bytes':
				return 1048576;
			case 'time_secs':
				return 3600;
			case 'time_mins':
				return 90;
			default:
				return null;
		}
	}
}
