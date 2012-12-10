<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Migrate templates to the new plugin

if(!isset($tables['devblocks_template']))
	return FALSE;

$db->Execute("UPDATE devblocks_template SET plugin_id = 'cerberusweb.support_center' WHERE plugin_id = 'usermeet.core'");

// ===========================================================================
// Migrate login handlers to multiple login extensions

if(!isset($tables['community_tool_property']))
	return FALSE;

$db->Execute("INSERT INTO community_tool_property (tool_code, property_key, property_value) ".
	"SELECT tool_code, 'common.login_extensions', property_value ".
	"FROM community_tool_property ".
	"WHERE property_key = 'common.login_handler' ".
	"AND property_value <> ''"
);
$db->Execute("DELETE FROM community_tool_property WHERE property_key = 'common.login_handler'");

// ===========================================================================
// contact_person_address_share
 
if(!isset($tables['supportcenter_address_share'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS supportcenter_address_share (
			share_address_id INT UNSIGNED NOT NULL,
			with_address_id INT UNSIGNED NOT NULL,
			is_enabled TINYINT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (share_address_id, with_address_id),
			INDEX share_address_id (share_address_id),
			INDEX with_address_id (with_address_id),
			INDEX is_enabled (is_enabled)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['supportcenter_address_share'] = 'supportcenter_address_share';
}

return TRUE;