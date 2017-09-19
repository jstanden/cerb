<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// webhook_listener

if(!isset($tables['webhook_listener'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS webhook_listener (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			guid VARCHAR(40) DEFAULT '',
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			extension_id VARCHAR(255) DEFAULT '',
			extension_params_json TEXT NOT NULL,
			PRIMARY KEY (id),
			INDEX guid (guid(3))
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['webhook_listener'] = 'webhook_listener';
}

return TRUE;