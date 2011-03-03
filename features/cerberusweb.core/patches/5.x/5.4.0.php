<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// Rename 'worker_event' to 'notification'

if(isset($tables['worker_event']) && !isset($tables['notification'])) {
	$db->Execute('ALTER TABLE worker_event RENAME notification');
	$db->Execute("DELETE FROM worker_view_model WHERE view_id = 'home_myevents'");
}

// ===========================================================================
// Import the 'community' tables from usermeet.core

// create community_tool
if(!isset($tables['community_tool'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_tool (
			id INT UNSIGNED DEFAULT 0 NOT NULL AUTO_INCREMENT,
			name VARCHAR(128) DEFAULT '' NOT NULL,
			code VARCHAR(8) DEFAULT '' NOT NULL,
			extension_id VARCHAR(128) DEFAULT '' NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
	
} else { // update community_tool
	list($columns, $indexes) = $db->metaTable('community_tool');
	
	if(isset($columns['id']) 
		&& ('int(10) unsigned' != $columns['id']['type'] 
		|| 'auto_increment' != $columns['id']['extra']))	
			$db->Execute("ALTER TABLE community_tool MODIFY COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT");	
	
	if(isset($columns['community_id']))
		$db->Execute("ALTER TABLE community_tool DROP COLUMN community_id");
		
	if(!isset($columns['name'])) {
	    $db->Execute("ALTER TABLE community_tool ADD COLUMN name VARCHAR(128) DEFAULT '' NOT NULL");
		$db->Execute("UPDATE community_tool SET name = 'Support Center' WHERE name = '' AND extension_id = 'sc.tool'");
	}
}

// create community_tool_property
if(!isset($tables['community_tool_property'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_tool_property (
			tool_code VARCHAR(8) DEFAULT '' NOT NULL,
			property_key VARCHAR(64) DEFAULT '' NOT NULL,
			property_value TEXT,
			PRIMARY KEY (tool_code, property_key)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
	
} else { // update community_tool_property
	list($columns, $indexes) = $db->metaTable('community_tool_property');
	
	if(isset($columns['property_value'])
		&& 0 != strcasecmp('text',$columns['property_value']['type'])) {
			$db->Execute("ALTER TABLE community_tool_property MODIFY COLUMN property_value TEXT");
	}
}

// create community_session
if(!isset($tables['community_session'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS community_session (
			session_id VARCHAR(32) DEFAULT '' NOT NULL,
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			properties MEDIUMTEXT,
			PRIMARY KEY (session_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
	
} else { // update community_session
	list($columns, $indexes) = $db->metaTable('community_session');

	if(isset($columns['properties'])
		&& 0 != strcasecmp('mediumtext',$columns['properties']['type'])) {
			$db->Execute('ALTER TABLE community_session MODIFY COLUMN properties MEDIUMTEXT');
	}
}


// community
if(isset($tables['community']))
	$db->Execute("DROP TABLE community");

return TRUE;
