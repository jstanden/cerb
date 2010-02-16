<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ***** Application

if(!isset($tables['community_tool'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_tool (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			code VARCHAR(8) DEFAULT '' NOT NULL,
			community_id INT UNSIGNED DEFAULT 0 NOT NULL,
			extension_id VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

return TRUE;