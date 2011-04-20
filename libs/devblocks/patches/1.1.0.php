<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

// ============================================================================
// Drop deprecated acl.is_default

list($columns, $indexes) = $db->metaTable($prefix.'acl');

if(isset($columns['is_default'])) {
	$db->Execute("ALTER TABLE ${prefix}acl DROP COLUMN is_default");
}

// ============================================================================
// Classloading cache from plugin manifests

if(!isset($tables[$prefix.'class_loader'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS ${prefix}class_loader (
			class VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			rel_path VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (class)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}

// ============================================================================
// Front controller cache from plugin manifests

if(!isset($tables[$prefix.'uri_routing'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS ${prefix}uri_routing (
			uri VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			controller_id VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (uri)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);
}

return TRUE;