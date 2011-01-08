<?php 
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

if(!isset($tables['feed'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS feed (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			url VARCHAR(255) DEFAULT '',
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

if(!isset($tables['feed_item'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS feed_item (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			feed_id INT UNSIGNED NOT NULL,
			guid VARCHAR(64) DEFAULT '',
			title VARCHAR(255) DEFAULT '',
			url VARCHAR(255) DEFAULT '',
			created_date INT UNSIGNED DEFAULT 0 NOT NULL,
			is_closed TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id),
			INDEX feed_id (feed_id),
			INDEX guid (guid(4)),
			INDEX created_date (created_date),
			INDEX is_closed (is_closed)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

return TRUE;