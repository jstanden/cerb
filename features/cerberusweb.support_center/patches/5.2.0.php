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

return TRUE;