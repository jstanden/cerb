<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Create initial tables

if(!isset($tables['server'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS server (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			owner INT UNSIGNED NULL DEFAULT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['server'] = 'server';
}

if (!isset($tables['server_to_contact'])) {
	$sql = sprintf("
		CREATE TABLE `server_to_contact` (
			`server_id` int(11) NOT NULL,
			`contact_id` int(11) NOT NULL,
			PRIMARY KEY (`server_id`,`contact_id`)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
	
	$tables['server_to_contact'] = 'server_to_contact';
}

return TRUE;