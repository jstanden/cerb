<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();
$revision = $db->GetOneMaster("SELECT revision FROM cerb_patch_history WHERE plugin_id = 'cerberusweb.core'");

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
// Finish up

return TRUE;
