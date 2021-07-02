<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add new automation events

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'mail.draft'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('mail.draft'),
		$db->qstr('cerb.trigger.mail.draft'),
		$db->qstr('Modify a new or resumed draft before the editor is opened'),
		$db->qstr(''),
		time()
	));
}

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'mail.received'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('mail.received'),
		$db->qstr('cerb.trigger.mail.received'),
		$db->qstr('After a new email message is received'),
		$db->qstr(''),
		time()
	));
}

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'mail.send'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('mail.send'),
		$db->qstr('cerb.trigger.mail.send'),
		$db->qstr('Before a sent message is delivered'),
		$db->qstr(''),
		time()
	));
}

if(!$db->GetOneMaster("SELECT 1 FROM automation_event WHERE name = 'mail.sent'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO automation_event (name, extension_id, description, automations_kata, updated_at) VALUES (%s,%s,%s,%s,%d)',
		$db->qstr('mail.sent'),
		$db->qstr('cerb.trigger.mail.sent'),
		$db->qstr('After a sent message is delivered'),
		$db->qstr(''),
		time()
	));
}

// ===========================================================================
// Add new toolbars

if(!$db->GetOneMaster("SELECT 1 FROM toolbar WHERE name = 'draft.read'")) {
	$db->ExecuteMaster(sprintf('INSERT IGNORE INTO toolbar (name, extension_id, description, toolbar_kata, created_at, updated_at) VALUES (%s,%s,%s,%s,%d,%d)',
		$db->qstr('draft.read'),
		$db->qstr('cerb.toolbar.draft.read'),
		$db->qstr('Reading a draft'),
		$db->qstr(''),
		time(),
		time()
	));
}

// ===========================================================================
// Add an index for matching numeric custom fields, optimizing record links 

if(!isset($tables['custom_field_numbervalue'])) {
	$logger->error("The 'custom_field_numbervalue' table does not exist.");
	return FALSE;
}

list(, $indexes) = $db->metaTable('custom_field_numbervalue');

if(!array_key_exists('field_id_and_value', $indexes)) {
	$db->ExecuteMaster("ALTER TABLE custom_field_numbervalue ADD INDEX field_id_and_value (field_id,field_value)");
}

// ===========================================================================
// Add a `name` prefix index to all custom record tables

foreach(array_keys($tables) as $table_name) {
	if(!DevblocksPlatform::strStartsWith($table_name, 'custom_record_'))
		continue;
	
	list(,$indexes) = $db->metaTable($table_name);
	
	if(!array_key_exists('name', $indexes)) {
		/** @noinspection SqlResolve */
		$db->ExecuteWriterOrFail(
			sprintf("ALTER TABLE %s ADD INDEX name (name(6))",
				$db->escape($table_name)
			),
			sprintf('10.1: Failed to add index %s.name', $table_name)
		);
	}
}

// ===========================================================================
// Reindex `storage_message_content`

list(,$indexes) = $db->metaTable('storage_message_content');

$changes = [];

if(array_key_exists('id', $indexes)) {
	$changes[] = 'DROP INDEX id';
}

if(array_key_exists('chunk', $indexes)) {
	$changes[] = 'DROP INDEX chunk';
}

if(!array_key_exists('id_and_chunk', $indexes)) {
	$changes[] = 'ADD INDEX id_and_chunk (id, chunk)';
}

if($changes) {
	$sql = "ALTER TABLE storage_message_content " . implode(', ', $changes);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Reindex `storage_resources`

list(,$indexes) = $db->metaTable('storage_resources');

$changes = [];

if(array_key_exists('id', $indexes)) {
	$changes[] = 'DROP INDEX id';
}

if(array_key_exists('chunk', $indexes)) {
	$changes[] = 'DROP INDEX chunk';
}

if(!array_key_exists('id_and_chunk', $indexes)) {
	$changes[] = 'ADD INDEX id_and_chunk (id, chunk)';
}

if($changes) {
	$sql = "ALTER TABLE storage_resources " . implode(', ', $changes);
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Update built-in automations

$automation_files = [
	'ai.cerb.automation.create.json',
	'ai.cerb.eventHandler.automation.json',
	'ai.cerb.eventHandler.automation.mail.received.json',
];

foreach($automation_files as $automation_file) {
	$path = realpath(APP_PATH . '/features/cerberusweb.core/assets/automations/') . '/' . $automation_file;
	
	if(!file_exists($path) || false === ($automation_data = json_decode(file_get_contents($path), true)))
		continue;
	
	DAO_Automation::importFromJson($automation_data);
	
	unset($automation_data);
}

// ===========================================================================
// Update package library

$packages = [
	'cerb_workspace_widget_chart_categories.json',
	'cerb_workspace_widget_chart_sheet.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

// ===========================================================================
// Finish up

return TRUE;