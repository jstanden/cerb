<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add index to `context_to_custom_fieldset`

list($columns, $indexes) = $db->metaTable('context_to_custom_fieldset');

if(!isset($indexes['context_and_id']))
	$db->ExecuteMaster('ALTER TABLE context_to_custom_fieldset ADD INDEX context_and_id (context, context_id)');

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automationBuilder.autocomplete.d3Format.json',
	'ai.cerb.automationBuilder.autocomplete.d3TimeFormat.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Finish up

return TRUE;

