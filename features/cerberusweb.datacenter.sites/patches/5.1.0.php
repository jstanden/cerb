<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Create initial tables

if(!isset($tables['datacenter_site'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS datacenter_site (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			domain VARCHAR(255) DEFAULT '',
			server_id INT UNSIGNED NOT NULL DEFAULT 0,
			created INT UNSIGNED NOT NULL DEFAULT 0,
			monthly_rate DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
			PRIMARY KEY (id),
			INDEX created (created),
			INDEX server_id (server_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['datacenter_site'] = 'datacenter_site';
}

return TRUE;