<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add `timezone`, `time_format`, `language`, and `calendar_id` to `worker`

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

if(!isset($columns['calendar_id'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN calendar_id INT UNSIGNED NOT NULL DEFAULT 0");

	// Move the worker preference to the worker record
	$db->Execute("UPDATE worker INNER JOIN worker_pref ON (worker_pref.worker_id=worker.id AND worker_pref.setting = 'availability_calendar_id') SET worker.calendar_id = worker_pref.value");
	
	// Remove the old worker pref
	$db->Execute("DELETE FROM worker_pref WHERE setting = 'availability_calendar_id'");
}

if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE worker ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute(sprintf("UPDATE worker SET updated = %d", time()));
}

// ===========================================================================
// Add `updated` columns to records that lack them

// Orgs

list($columns, $indexes) = $db->metaTable('contact_org');

if(!isset($tables['contact_org'])) {
	$logger->error("The 'contact_org' table does not exist.");
	return FALSE;
}

if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE contact_org ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0");
}

// Contacts

list($columns, $indexes) = $db->metaTable('contact_person');

if(!isset($tables['contact_person'])) {
	$logger->error("The 'contact_person' table does not exist.");
	return FALSE;
}

if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE contact_person ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0");
}

// Groups

list($columns, $indexes) = $db->metaTable('worker_group');

if(!isset($tables['worker_group'])) {
	$logger->error("The 'worker_group' table does not exist.");
	return FALSE;
}

if(!isset($columns['created'])) {
	$db->Execute("ALTER TABLE worker_group ADD COLUMN created INT UNSIGNED NOT NULL DEFAULT 0");
	$db->Execute(sprintf("UPDATE worker_group SET created = %d", time()));
}

if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE worker_group ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0");
}

// ===========================================================================
// Clean up unused worker prefs

$db->Execute("DELETE FROM worker_pref WHERE setting LIKE 'quicksearch_%'");

// ===========================================================================
// Finish up

return TRUE;
