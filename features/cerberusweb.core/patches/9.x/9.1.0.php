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
// Add `custom_field_geovalue`

if(!isset($tables['custom_field_geovalue'])) {
	$sql = sprintf("
		CREATE TABLE `custom_field_geovalue` (
		`field_id` int(10) unsigned NOT NULL DEFAULT '0',
		`context_id` int(10) unsigned NOT NULL DEFAULT '0',
		`field_value` POINT NOT NULL,
		`context` varchar(255) NOT NULL DEFAULT '',
		KEY `field_id` (`field_id`),
		KEY `context_and_id` (`context`,`context_id`),
		SPATIAL INDEX (field_value)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['custom_field_geovalue'] = 'custom_field_geovalue';
}

// ===========================================================================
// Finish up

return TRUE;
