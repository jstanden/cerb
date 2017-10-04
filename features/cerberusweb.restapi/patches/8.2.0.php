<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ===========================================================================
// Add `updated_at` to the `webapi_credentials` table

if(!isset($tables['webapi_credentials'])) {
	$logger->error("The 'webapi_credentials' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('webapi_credentials');

if(!isset($columns['updated_at'])) {
	$sql = 'ALTER TABLE webapi_credentials ADD COLUMN updated_at int(10) unsigned NOT NULL DEFAULT 0';
	$db->ExecuteMaster($sql);
	
	$db->ExecuteMaster(sprintf("UPDATE webapi_credentials SET updated_at = %d", time()));
}

if(isset($columns['label'])) {
	$sql = "ALTER TABLE webapi_credentials CHANGE COLUMN label name varchar(255) NOT NULL DEFAULT ''";
	$db->ExecuteMaster($sql);
}

return TRUE;