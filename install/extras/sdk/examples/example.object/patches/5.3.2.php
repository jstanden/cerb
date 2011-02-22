<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// example_object 

if(!isset($tables['example_object'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS example_object (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			created INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			INDEX created (created)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['example_object'] = 'example_object';
}

return TRUE;
