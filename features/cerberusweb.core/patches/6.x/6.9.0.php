<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add `timezone`, `time_format` and `language` to `worker`

if(!isset($tables['worker'])) {
	$logger->error("The 'worker' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('worker');

if(!isset($columns['timezone'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN timezone VARCHAR(255) NOT NULL DEFAULT ''");
	$db->Execute("UPDATE worker SET timezone = 'UTC'");

	// Move the worker preference to the worker record
	$db->Execute("UPDATE worker INNER JOIN worker_pref ON (worker_pref.worker_id=worker.id AND worker_pref.setting = 'timezone') SET worker.timezone = worker_pref.value");
	
	// Remove the old worker pref
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'timezone'");
}

if(!isset($columns['time_format'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN time_format VARCHAR(64) NOT NULL DEFAULT ''");
	$db->Execute("UPDATE worker SET time_format = 'D, d M Y h:i a'");

	// Move the worker preference to the worker record
	$db->Execute("UPDATE worker INNER JOIN worker_pref ON (worker_pref.worker_id=worker.id AND worker_pref.setting = 'time_format') SET worker.time_format = worker_pref.value");
	
	// Remove the old worker pref
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'time_format'");
}

if(!isset($columns['language'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN language VARCHAR(16) NOT NULL DEFAULT ''");
	$db->Execute("UPDATE worker SET language = 'en_US'");

	// Move the worker preference to the worker record
	$db->Execute("UPDATE worker INNER JOIN worker_pref ON (worker_pref.worker_id=worker.id AND worker_pref.setting = 'locale') SET worker.language = worker_pref.value");
	
	// Remove the old worker pref
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'locale'");
}

// ===========================================================================
// Finish up

return TRUE;
