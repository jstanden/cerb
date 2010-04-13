<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `webapi_key` ========================
if(!isset($tables['webapi_key'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS webapi_key (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			nickname VARCHAR(64) DEFAULT '' NOT NULL,
			access_key VARCHAR(32) DEFAULT '' NOT NULL,
			secret_key VARCHAR(40) DEFAULT '' NOT NULL,
			rights MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('webapi_key');

if(!isset($indexes['access_key'])) {
	$db->Execute('ALTER TABLE webapi_key ADD INDEX access_key (access_key)');
}

return TRUE;
