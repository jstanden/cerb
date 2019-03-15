<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

list($columns,) = $db->metaTable('cerb_plugin');

if(isset($columns['revision'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin DROP COLUMN revision");
}

if(!isset($columns['version'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin ADD COLUMN version SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `author`");
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
	$db->ExecuteMaster($sql);
}

return TRUE;