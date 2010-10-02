<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ===========================================================================
// Add nicknames for community tool instances

list($columns, $indexes) = $db->metaTable('community_tool');

if(!isset($columns['name'])) {
    $db->Execute("ALTER TABLE community_tool ADD COLUMN name VARCHAR(128) DEFAULT '' NOT NULL");
	$db->Execute("UPDATE community_tool SET name = 'Support Center' WHERE name = '' AND extension_id = 'sc.tool'");
}

// ===========================================================================
// Change 'community_tool_property' values to X (text) rather than B (blob)

if(isset($tables['community_tool_property'])) {
	list($columns, $indexes) = $db->metaTable('community_tool_property');
	
	if(isset($columns['property_value'])) {
		if(0 != strcasecmp('text',$columns['property_value']['type'])) {
			$db->Execute("ALTER TABLE community_tool_property CHANGE COLUMN property_value property_value TEXT");
		}
	}
}

return TRUE;
