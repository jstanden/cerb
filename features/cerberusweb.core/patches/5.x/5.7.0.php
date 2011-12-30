<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Plugin library

if(!isset($tables['plugin_library'])) {
	$sql = sprintf("CREATE TABLE IF NOT EXISTS plugin_library (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		plugin_id VARCHAR(255) NOT NULL DEFAULT 0,
		name VARCHAR(255) NOT NULL DEFAULT '',
		author VARCHAR(255) NOT NULL DEFAULT '',
		description TEXT,
		link VARCHAR(255) NOT NULL DEFAULT '',
		latest_version SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		icon_url VARCHAR(255) NOT NULL DEFAULT '',
		requirements_json TEXT,
		updated INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (plugin_id),
		INDEX (latest_version),
		INDEX (updated)
	) ENGINE=%s", APP_DB_ENGINE);
	
	if(false === $db->Execute($sql))
		return FALSE;
		
	$tables['plugin_library'] = 'plugin_library';
}

// ===========================================================================
// Add variables to trigger_event

if(!isset($tables['trigger_event'])) {
	$logger->error("The 'trigger_event' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('trigger_event');

if(!isset($columns['variables_json'])) {
	$db->Execute("ALTER TABLE trigger_event ADD COLUMN variables_json TEXT");
}

return TRUE;