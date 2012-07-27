<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Add address.is_defunct

if(!isset($tables['address'])) {
	$logger->error("The 'address' table does not exist.");
	return FALSE;
}

list($columns, $indexes) = $db->metaTable('address');

if(!isset($columns['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD COLUMN is_defunct TINYINT UNSIGNED NOT NULL DEFAULT 0");
}

if(!isset($indexes['is_defunct'])) {
	$db->Execute("ALTER TABLE address ADD INDEX is_defunct (is_defunct)");
}

// ===========================================================================
// workspace_widget

if(!isset($tables['workspace_widget'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS workspace_widget (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			extension_id VARCHAR(255) DEFAULT '' NOT NULL,
			workspace_tab_id INT UNSIGNED DEFAULT 0 NOT NULL,
			label VARCHAR(255) DEFAULT '' NOT NULL,
			updated_at INT UNSIGNED DEFAULT 0 NOT NULL,
			params_json TEXT,
			pos CHAR(4) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);

	$tables['workspace_widget'] = 'workspace_widget';
}

return TRUE;