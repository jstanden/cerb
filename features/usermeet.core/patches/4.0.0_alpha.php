<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// ***** Application

if(!isset($tables['community_tool'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_tool (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			code VARCHAR(8) DEFAULT '' NOT NULL,
			community_id INT UNSIGNED DEFAULT 0 NOT NULL,
			extension_id VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `community` ========================
list($columns, $indexes) = $db->metaTable('community');

if(isset($columns['URL'])) {
	$db->Execute('ALTER TABLE community DROP COLUMN url');
}

// `community_session` ========================
if(!isset($tables['community_session'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_session (
			session_id VARCHAR(32) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			properties BLOB,
			PRIMARY KEY (session_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

// `community_tool` =============================
list($columns, $indexes) = $db->metaTable('community_tool');

if(!isset($indexes['community_id'])) {
	$db->Execute('ALTER TABLE community_tool ADD INDEX community_id (community_id)');
}

// `community_tool_property` ========================
if(!isset($tables['community_tool_property'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_tool_property (
			tool_code VARCHAR(8) DEFAULT '' NOT NULL,
			property_key VARCHAR(64) DEFAULT '' NOT NULL,
			property_value BLOB,
			PRIMARY KEY (tool_code, property_key)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}

return TRUE;