<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Remove the worker last activity fields

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(isset($columns['last_activity'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity");
}

if(isset($columns['last_activity_date'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity_date");
}

if(isset($columns['last_activity_ip'])) {
	$db->ExecuteMaster("ALTER TABLE worker DROP COLUMN last_activity_ip");
}

// ===========================================================================
// Remove the old _version file if it exists

if(file_exists(APP_STORAGE_PATH . '/_version'))
	@unlink(APP_STORAGE_PATH . '/_version');

// ===========================================================================
// Finish up

return TRUE;
