<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// call
if(!isset($tables['call_entry'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS call_entry (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			phone VARCHAR(128) DEFAULT '' NOT NULL,
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			updated_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_outgoing TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

list($columns, $indexes) = $db->metaTable('call_entry');

if(!isset($indexes['created_date'])) {
	$db->Execute('ALTER TABLE call_entry ADD INDEX created_date (created_date)');
}

if(!isset($indexes['updated_date'])) {
    $db->Execute('ALTER TABLE call_entry ADD INDEX updated_date (updated_date)');
}

if(!isset($indexes['is_outgoing'])) {
    $db->Execute('ALTER TABLE call_entry ADD INDEX is_outgoing (is_outgoing)');
}

return TRUE;