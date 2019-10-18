<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Add updated field to domain records

if(!isset($tables['datacenter_domain'])) {
	$logger->error("The 'datacenter_domain' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('datacenter_domain');

if(!isset($columns['updated'])) {
	$db->ExecuteMaster("ALTER TABLE datacenter_domain ADD COLUMN updated INT UNSIGNED DEFAULT 0");
}

return TRUE;