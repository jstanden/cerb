<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Create initial tables

if(!isset($tables['server'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS server (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['server'] = 'server';
}

return TRUE;