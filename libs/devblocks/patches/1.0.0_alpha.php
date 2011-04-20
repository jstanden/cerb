<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : '';

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}event_point (
		id VARCHAR(255) DEFAULT '' NOT NULL,
		plugin_id VARCHAR(255) DEFAULT '0' NOT NULL,
		name VARCHAR(255) DEFAULT '' NOT NULL,
		params MEDIUMBLOB,
		PRIMARY KEY (id)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}extension_point (
		id VARCHAR(255) DEFAULT '' NOT NULL,
		plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}extension (
		id VARCHAR(255) DEFAULT '' NOT NULL,
		plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
		point VARCHAR(255) DEFAULT '' NOT NULL,
		pos SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
		name VARCHAR(255) DEFAULT '' NOT NULL,
		file VARCHAR(255) DEFAULT '' NOT NULL,
		class VARCHAR(255) DEFAULT '' NOT NULL,
		params MEDIUMBLOB,
		PRIMARY KEY (id)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}patch_history (
		plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
		revision MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL,
		run_date INT UNSIGNED DEFAULT 0 NOT NULL,
		PRIMARY KEY (plugin_id)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}plugin (
		id VARCHAR(255),
		enabled TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
		name VARCHAR(255) DEFAULT '' NOT NULL,
		description VARCHAR(255) DEFAULT '' NOT NULL,
		author VARCHAR(64) DEFAULT '' NOT NULL,
		revision INT UNSIGNED DEFAULT 0 NOT NULL,
		dir VARCHAR(255) DEFAULT '' NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}property_store (
		extension_id VARCHAR(128) DEFAULT '' NOT NULL,
		instance_id TINYINT UNSIGNED DEFAULT 0 NOT NULL,
		property VARCHAR(128) DEFAULT '' NOT NULL,
		value VARCHAR(255) DEFAULT '' NOT NULL,
		PRIMARY KEY (extension_id, instance_id, property)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

$sql = sprintf("
	CREATE TABLE IF NOT EXISTS ${prefix}session (
		sesskey VARCHAR(64),
		expiry DATETIME,
		expireref VARCHAR(250),
		created DATETIME NOT NULL,
		modified DATETIME NOT NULL,
		sessdata MEDIUMBLOB,
		PRIMARY KEY (sesskey)
	) ENGINE=%s;
", APP_DB_ENGINE);
$db->Execute($sql);

return TRUE;
