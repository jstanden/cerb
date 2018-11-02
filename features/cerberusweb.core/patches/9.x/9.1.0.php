<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Alter `custom_field`

if(!isset($tables['custom_field']))
	return FALSE;

list($columns,) = $db->metaTable('custom_field');

if($columns['type'] && 0 == strcasecmp('varchar(1)', $columns['type']['type'])) {
	$db->ExecuteMaster("ALTER TABLE custom_field MODIFY COLUMN type VARCHAR(255)");
}

// ===========================================================================
// Finish up

return TRUE;
