<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Contact lists 

if(!isset($tables['contact_list'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS contact_list (
			id INT UNSIGNED AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['contact_list'] = 'contact_list';
}

return TRUE;
