<?php
$db = DevblocksPlatform::services()->database();
$tables = $db->metaTables();

// `plugin` ========================
list($columns,) = $db->metaTable('cerb_plugin');

if(!isset($columns['file'])) {
	$sql = "ALTER TABLE cerb_plugin ADD COLUMN file VARCHAR(128) DEFAULT '' NOT NULL";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['class'])) {
	$sql = "ALTER TABLE cerb_plugin ADD COLUMN class VARCHAR(128) DEFAULT '' NOT NULL";
	$db->ExecuteMaster($sql);
}

if(!isset($columns['link'])) {
	$sql = "ALTER TABLE cerb_plugin ADD COLUMN link VARCHAR(128) DEFAULT '' NOT NULL";
	$db->ExecuteMaster($sql);
}

if(isset($columns['is_configurable'])) {
	$sql = "ALTER TABLE cerb_plugin DROP COLUMN is_configurable";
	$db->ExecuteMaster($sql);
}

// `property_store` ========================
list($columns,) = $db->metaTable('cerb_property_store');

if(isset($columns['value']) && 0==strcasecmp('varchar',substr($columns['value']['type'],0,7))) {
	$db->ExecuteMaster("ALTER TABLE cerb_property_store CHANGE COLUMN value value_old VARCHAR(255) DEFAULT '' NOT NULL");
	$db->ExecuteMaster("ALTER TABLE cerb_property_store ADD COLUMN value MEDIUMBLOB");
	
	$sql = "SELECT extension_id, instance_id, property, value_old FROM cerb_property_store ";
	$rs = $db->GetArrayMaster($sql);
	
	foreach($rs as $row) {
		$sql = sprintf(
			"UPDATE cerb_property_store ".
			"SET value=%s ".
			"WHERE extension_id = %s ".
			"AND instance_id = %s ".
			"AND property = %s",
			$db->qstr($row['value_old']),
			$db->qstr($row['extension_id']),
			$db->qstr($row['instance_id']),
			$db->qstr($row['property'])
		);
	}
	
	$db->ExecuteMaster("ALTER TABLE cerb_property_store DROP COLUMN value_old");
}

// `translation` ========================
if(!isset($tables['translation'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS translation (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			string_id VARCHAR(255) DEFAULT '' NOT NULL,
			lang_code VARCHAR(16) DEFAULT '' NOT NULL,
			string_default LONGTEXT,
			string_override LONGTEXT,
			PRIMARY KEY (id),
			INDEX string_id (string_id),
			INDEX lang_code (lang_code)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);
}

// ============================================================================
// ACL privileges from plugins

if(!isset($tables['cerb_acl'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS cerb_acl (
			id VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			label VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);	
}	

return TRUE;