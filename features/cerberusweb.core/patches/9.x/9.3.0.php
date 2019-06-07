<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Update package library

$packages = [
	'cerb_bot_behavior_form_interaction_worker.json',
	'cerb_profile_widget_ticket_participants.json',
	'cerb_workspace_widget_chart_sheet.json',
];

CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');

$sql = sprintf('DELETE FROM package_library WHERE uri = %s', $db->qstr('cerb_workspace_widget_chart_table'));
$db->ExecuteMaster($sql);

// ===========================================================================
// Add `portal_id` to `community_session`

list($columns,) = $db->metaTable('community_session');

if(!isset($columns['portal_id'])) {
	// Flush portal sessions
	$db->ExecuteMaster("DELETE FROM community_session");
	
	$sql = "ALTER TABLE community_session ADD COLUMN portal_id BIGINT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX (portal_id)";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Verify `custom_field_numbervalue` is signed

if(!isset($tables['custom_field_numbervalue'])) {
	//$logger->error("The 'custom_field_numbervalue' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('custom_field_numbervalue');

if(@$columns['field_value'] && 0 != strcasecmp($columns['field_value']['type'], 'bigint(20)')) {
	$db->ExecuteMaster("ALTER TABLE custom_field_numbervalue MODIFY COLUMN field_value BIGINT NOT NULL DEFAULT 0");
}

// ===========================================================================
// Add `extension_params_json` to `workspace_page`

list($columns,) = $db->metaTable('workspace_page');

if(!isset($columns['extension_params_json'])) {
	$sql = "ALTER TABLE workspace_page ADD COLUMN extension_params_json TEXT AFTER extension_id";
	$db->ExecuteMaster($sql);
}

// ===========================================================================
// Finish up

return TRUE;
