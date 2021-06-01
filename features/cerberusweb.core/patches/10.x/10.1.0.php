<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

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
// Finish up

return TRUE;