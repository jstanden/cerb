<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

// ===========================================================================
// Update package library

if($revision < 1341) { // 9.2.x -> 9.3
	$packages = [
		'cerb_profile_widget_ticket_participants.json',
	];
	
	CerberusApplication::packages()->importToLibraryFromFiles($packages, APP_PATH . '/features/cerberusweb.core/packages/library/');
}

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
// Finish up

return TRUE;
