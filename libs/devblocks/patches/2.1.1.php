<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();
$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(isset($columns['revision'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin DROP COLUMN revision");
}

if(!isset($columns['version'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin ADD COLUMN version SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `author`");
}

// ============================================================================
// devblocks_registry

if(!isset($tables['devblocks_registry'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_registry (
	    	entry_key VARCHAR(255) DEFAULT '' NOT NULL,
			entry_type VARCHAR(32) DEFAULT '' NOT NULL,
			entry_value TEXT,
			PRIMARY KEY (entry_key)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

return TRUE;