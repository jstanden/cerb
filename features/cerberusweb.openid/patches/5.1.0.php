<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Create initial tables

if(!isset($tables['openid_to_worker'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS openid_to_worker (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			openid_url VARCHAR(255) DEFAULT '',
			openid_claimed_id VARCHAR(255) DEFAULT '',
			worker_id INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (id),
			UNIQUE openid_claimed_id (openid_claimed_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['openid_to_worker'] = 'openid_to_worker';
}

return TRUE;