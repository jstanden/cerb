<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ============================================================================
// Drop deprecated acl.is_default

list($columns,) = $db->metaTable('cerb_acl');

if(isset($columns['is_default'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_acl DROP COLUMN is_default");
}

// ============================================================================
// Classloading cache from plugin manifests

if(!isset($tables['cerb_class_loader'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS cerb_class_loader (
			class VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			rel_path VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (class)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

// ============================================================================
// Front controller cache from plugin manifests

if(!isset($tables['cerb_uri_routing'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS cerb_uri_routing (
			uri VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			controller_id VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (uri)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

return TRUE;