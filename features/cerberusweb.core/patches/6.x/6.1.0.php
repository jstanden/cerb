<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add address.is_defunct

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN is_defunct TINYINT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($indexes['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD INDEX is_defunct (is_defunct)");
}

return TRUE;