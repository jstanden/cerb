<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

$prefix = (APP_DB_PREFIX != '') ? APP_DB_PREFIX.'_' : ''; // [TODO] Cleanup

// `plugin` ========================
list($columns, $indexes) = $db->metaTable($prefix.'plugin');

if(!isset($columns['file'])) {
	$sql = "ALTER TABLE ${prefix}plugin ADD COLUMN file VARCHAR(128) DEFAULT '' NOT NULL";
	$db->Execute($sql);
}

if(!isset($columns['class'])) {
	$sql = "ALTER TABLE ${prefix}plugin ADD COLUMN class VARCHAR(128) DEFAULT '' NOT NULL";
	$db->Execute($sql);
}

if(!isset($columns['link'])) {
	$sql = "ALTER TABLE ${prefix}plugin ADD COLUMN link VARCHAR(128) DEFAULT '' NOT NULL";
	$db->Execute($sql);
}

if(isset($columns['is_configurable'])) {
	$sql = "ALTER TABLE ${prefix}plugin DROP COLUMN is_configurable";
	$db->Execute($sql);
}

// `property_store` ========================
list($columns, $indexes) = $db->metaTable($prefix.'property_store');

if(isset($columns['value']) && 0==strcasecmp('varchar',substr($columns['value']['type'],0,7))) {
	$db->Execute("ALTER TABLE ${prefix}property_store CHANGE COLUMN value value_old VARCHAR(255) DEFAULT '' NOT NULL");
	$db->Execute("ALTER TABLE ${prefix}property_store ADD COLUMN value MEDIUMBLOB");
	
	$sql = "SELECT extension_id, instance_id, property, value_old FROM ${prefix}property_store ";
	$rs = $db->GetArray($sql);
	
	foreach($rs as $row) {
		$sql = sprintf(
			"UPDATE ${prefix}property_store ".
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
	
	$db->Execute("ALTER TABLE ${prefix}property_store DROP COLUMN value_old");
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
	$db->Execute($sql);
}

// ============================================================================
// ACL privileges from plugins

if(!isset($tables[$prefix.'acl'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS ${prefix}acl (
			id VARCHAR(255) DEFAULT '' NOT NULL,
			plugin_id VARCHAR(255) DEFAULT '' NOT NULL,
			label VARCHAR(255) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->Execute($sql);	
}	

return TRUE;