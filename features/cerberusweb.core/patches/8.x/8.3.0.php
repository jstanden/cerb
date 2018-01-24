<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Replace org.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.org.merge', 'activities.record.merge') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.org\",\"context_label\":\"organization\",') WHERE activity_point = 'org.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'org.merge'");

// ===========================================================================
// Replace ticket.merge with record.merge in activity log

$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = REPLACE(entry_json, 'activities.ticket.merge', 'activities.record.merge') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET entry_json = replace(entry_json, 'variables\":{', 'variables\":{\"context\":\"cerberusweb.contexts.ticket\",\"context_label\":\"ticket\",') WHERE activity_point = 'ticket.merge'");
$db->ExecuteMaster("UPDATE context_activity_log SET activity_point = 'record.merge' WHERE activity_point = 'ticket.merge'");

// ===========================================================================
// Add `updated_at` field to the `custom_fieldset` table

if(!isset($tables['custom_fieldset'])) {
	$logger->error("The 'custom_fieldset' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('custom_fieldset');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE custom_fieldset ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE custom_fieldset SET updated_at = %d", time()));
}

// ===========================================================================
// Add `updated_at` field to the `workspace_page` table

if(!isset($tables['workspace_page'])) {
	$logger->error("The 'workspace_page' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('workspace_page');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE workspace_page ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE workspace_page SET updated_at = %d", time()));
}

// ===========================================================================
// Finish up

return TRUE;
