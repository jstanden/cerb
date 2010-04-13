<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

// ============================================================================
// property_store updates

list($columns, $indexes) = $db->metaTable($prefix.'property_store');

// Fix blob encoding
if(isset($columns['value'])) {
	if(0 == strcasecmp('mediumblob',$columns['value']['type'])) {
		$sql = sprintf("ALTER TABLE ${prefix}property_store CHANGE COLUMN value value TEXT");
		$db->Execute($sql);
	}
}

// Drop instance ID
if(isset($columns['instance_id'])) {
	$db->Execute("ALTER TABLE ${prefix}property_store DROP COLUMN instance_id");
}

// ============================================================================
// plugin updates

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

// Drop 'file'
if(isset($columns['file'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin DROP COLUMN file");
}

// Drop 'class'
if(isset($columns['class'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin DROP COLUMN class");
}

// ============================================================================
// devblocks_setting

if(!isset($tables['devblocks_setting'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS devblocks_setting (
	    	plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			setting VARCHAR(32) DEFAULT '' NOT NULL,
			value VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (plugin_id, setting)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// ============================================================================
// devblocks_template

if(!isset($tables['devblocks_template'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS devblocks_template (
	    	id INT UNSIGNED DEFAULT 0 NOT NULL,
	    	plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			path VARCHAR(255) DEFAULT '' NOT NULL,
			tag VARCHAR(255) DEFAULT '' NOT NULL,
			last_updated INT UNSIGNED DEFAULT 0 NOT NULL,
			content MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// ===========================================================================
// Add 'template' manifests to 'plugin'

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(!isset($columns['templates_json'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin ADD COLUMN templates_json MEDIUMTEXT");
}

// ============================================================================
// Extension updates

list($columns, $indexes) = $db->metaTable($prefix.'extension');

// Fix blob encoding
if(isset($columns['params'])) {
	if(0==strcasecmp('mediumblob',$columns['params']['type'])) {
		$sql = sprintf("ALTER TABLE ${prefix}extension CHANGE COLUMN params params TEXT");
		$db->Execute($sql);
	}
}

// ============================================================================
// Drop ADODB sessions

if(isset($tables[$prefix.'session'])) {
	$db->Execute("DROP TABLE ${prefix}session");
	unset($tables[$prefix.'session']);	
}

// ============================================================================
// Add Devblocks-backed sessions

if(!isset($tables['devblocks_session'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS devblocks_session (
			session_key VARCHAR(64) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			session_data MEDIUMTEXT,
			PRIMARY KEY (session_key),
			INDEX created (created),
			INDEX updated (updated)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);
}

// ============================================================================
// Fix BLOBs

list($columns, $indexes) = $db->metaTable('devblocks_session');

if(isset($columns['session_data']) && 
	0 != strcasecmp('mediumtext', $columns['session_data']['type'])) {
		$db->Execute('ALTER TABLE devblocks_session MODIFY COLUMN session_data MEDIUMTEXT');		
}

list($columns, $indexes) = $db->metaTable($prefix.'event_point');

if(isset($columns['params'])
	&& 0 != strcasecmp('mediumtext',$columns['params']['type'])) {
		$db->Execute("ALTER TABLE ${prefix}event_point MODIFY COLUMN params MEDIUMTEXT");
}

// ============================================================================
// Storage profiles

if(!isset($tables['devblocks_storage_profile'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS devblocks_storage_profile (
			id int(11) NOT NULL DEFAULT 0,
			name varchar(128) NOT NULL DEFAULT '',
			extension_id varchar(255) NOT NULL DEFAULT '',
			params_json longtext,
			PRIMARY KEY (id),
			INDEX extension_id (extension_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql) or die($db->ErrorMsg());
}

// ============================================================================
// Force enable 'devblocks.core' plugin

$sql = sprintf("UPDATE %splugin SET enabled=1 WHERE id='devblocks.core'",
	$prefix
);
$db->Execute($sql) or die($db->ErrorMsg());

// ============================================================================
// Resize 'devblocks_setting' values

list($columns, $indexes) = $db->metaTable('devblocks_setting');

if(isset($columns['value'])
	&& 0 != strcasecmp('text',$columns['value']['type'])) {
		$db->Execute("ALTER TABLE devblocks_setting MODIFY COLUMN value TEXT");
}

// ===========================================================================
// Drop templates_json and move to manifest_cache_json

list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(isset($columns['templates_json'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin DROP COLUMN templates_json");
}

if(!isset($columns['manifest_cache_json'])) {
	$db->Execute("ALTER TABLE ${prefix}plugin ADD COLUMN manifest_cache_json MEDIUMTEXT");
}

return TRUE;
