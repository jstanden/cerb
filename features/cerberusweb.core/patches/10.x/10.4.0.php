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
	'cerb.ticket.move.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Add new toolbars

if(!$db->GetOneMaster("SELECT 1 FROM toolbar WHERE name = 'records.worklist'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s,%s,%s,%s,%d,%d)',
		$db->qstr('records.worklist'),
		$db->qstr('cerb.toolbar.records.worklist'),
		$db->qstr('Viewing a worklist of records'),
		$db->qstr(''),
		time(),
		time()
	));
}

// ===========================================================================
// Finish up

return TRUE;

