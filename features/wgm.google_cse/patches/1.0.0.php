<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `wgm_google_cse` ========================
if(!isset($tables['wgm_google_cse'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS wgm_google_cse (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			name VARCHAR(255) DEFAULT '' NOT NULL,
			url VARCHAR(255) DEFAULT '' NOT NULL,
			token VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

return TRUE;
