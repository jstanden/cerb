<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// ============================================================================
// property_store updates

list($columns,) = $db->metaTable('cerb_property_store');

// Fix blob encoding
if(isset($columns['value'])) {
	if(0 == strcasecmp('mediumblob',$columns['value']['type'])) {
		$sql = sprintf("ALTER TABLE cerb_property_store CHANGE COLUMN value value TEXT");
		$db->ExecuteMaster($sql);
	}
}

// Drop instance ID
if(isset($columns['instance_id'])) {
	$db->ExecuteMaster("DELETE FROM cerb_property_store WHERE instance_id > 0");
	$db->ExecuteMaster("ALTER TABLE cerb_property_store DROP COLUMN instance_id");
}

// ============================================================================
// plugin updates

list($columns,) = $db->metaTable('cerb_plugin');

// Drop 'file'
if(isset($columns['file'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin DROP COLUMN file");
}

// Drop 'class'
if(isset($columns['class'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin DROP COLUMN class");
}

// ============================================================================
// devblocks_setting

if(!isset($tables['devblocks_setting'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_setting (
	    	plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			setting VARCHAR(32) DEFAULT '' NOT NULL,
			value VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (plugin_id, setting)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);	
}

// ============================================================================
// devblocks_template

if(!isset($tables['devblocks_template'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_template (
	    	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	    	plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			path VARCHAR(255) DEFAULT '' NOT NULL,
			tag VARCHAR(255) DEFAULT '' NOT NULL,
			last_updated INT UNSIGNED DEFAULT 0 NOT NULL,
			content MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

// Update key
list($columns,) = $db->metaTable('devblocks_template');

if(isset($columns['id']) 
	&& ('int(10) unsigned' != $columns['id']['type'] 
	|| 'auto_increment' != $columns['id']['extra'])
) {
	$db->ExecuteMaster("ALTER TABLE devblocks_template MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT");
}

// ===========================================================================
// Add 'template' manifests to 'plugin'

list($columns,) = $db->metaTable('cerb_plugin');

if(!isset($columns['templates_json'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin ADD COLUMN templates_json MEDIUMTEXT");
}

// ============================================================================
// Extension updates

list($columns,) = $db->metaTable('cerb_extension');

// Fix blob encoding
if(isset($columns['params'])) {
	if(0==strcasecmp('mediumblob',$columns['params']['type'])) {
		$sql = sprintf("ALTER TABLE cerb_extension CHANGE COLUMN params params TEXT");
		$db->ExecuteMaster($sql);
	}
}

// ============================================================================
// Drop ADODB sessions

if(isset($tables['cerb_session'])) {
	$db->ExecuteMaster("DROP TABLE cerb_session");
	unset($tables['cerb_session']);	
}

// ============================================================================
// Add Devblocks-backed sessions

if(!isset($tables['devblocks_session'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_session (
			session_key VARCHAR(64) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			session_data MEDIUMTEXT,
			PRIMARY KEY (session_key),
			INDEX created (created),
			INDEX updated (updated)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

// ============================================================================
// Fix BLOBs

list($columns,) = $db->metaTable('devblocks_session');

if(isset($columns['session_data']) && 
	0 != strcasecmp('mediumtext', $columns['session_data']['type'])) {
		$db->ExecuteMaster('ALTER TABLE devblocks_session MODIFY COLUMN session_data MEDIUMTEXT');		
}

list($columns,) = $db->metaTable('cerb_event_point');

if(isset($columns['params'])
	&& 0 != strcasecmp('mediumtext',$columns['params']['type'])) {
		$db->ExecuteMaster("ALTER TABLE cerb_event_point MODIFY COLUMN params MEDIUMTEXT");
}

// ============================================================================
// Storage profiles

if(!isset($tables['devblocks_storage_profile'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS devblocks_storage_profile (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(128) NOT NULL DEFAULT '',
			extension_id varchar(255) NOT NULL DEFAULT '',
			params_json longtext,
			PRIMARY KEY (id),
			INDEX extension_id (extension_id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die($db->ErrorMsg());
}

// ============================================================================
// Force enable 'devblocks.core' plugin

$sql = "UPDATE cerb_plugin SET enabled=1 WHERE id='devblocks.core'";
$db->ExecuteMaster($sql) or die($db->ErrorMsg());

// ============================================================================
// Resize 'devblocks_setting' values

list($columns,) = $db->metaTable('devblocks_setting');

if(isset($columns['value'])
	&& 0 != strcasecmp('text',$columns['value']['type'])) {
		$db->ExecuteMaster("ALTER TABLE devblocks_setting MODIFY COLUMN value TEXT");
}

// ===========================================================================
// Drop templates_json and move to manifest_cache_json

list($columns,) = $db->metaTable('cerb_plugin');

if(isset($columns['templates_json'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin DROP COLUMN templates_json");
}

if(!isset($columns['manifest_cache_json'])) {
	$db->ExecuteMaster("ALTER TABLE cerb_plugin ADD COLUMN manifest_cache_json MEDIUMTEXT");
}

return TRUE;
