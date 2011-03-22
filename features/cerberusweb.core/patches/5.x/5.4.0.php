<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// trigger_event 

if(!isset($tables['trigger_event'])) {
	$sql = "
	CREATE TABLE IF NOT EXISTS `trigger_event` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		title VARCHAR(255) NOT NULL DEFAULT '',
		is_disabled TINYINT NOT NULL DEFAULT 0,
		owner_context VARCHAR(255) NOT NULL DEFAULT '',
		owner_context_id INT UNSIGNED NOT NULL DEFAULT 0,
		event_point VARCHAR(255) NOT NULL DEFAULT '',
		PRIMARY KEY (id),
		INDEX event_point (event_point)
	) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['trigger_event'] = 'trigger_event';
}

// ===========================================================================
// decision_node

if(!isset($tables['decision_node'])) {
	$sql = "
	CREATE TABLE IF NOT EXISTS decision_node (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		parent_id INT UNSIGNED NOT NULL DEFAULT 0,
		trigger_id INT UNSIGNED NOT NULL DEFAULT 0,
		node_type ENUM('switch','outcome','action'),
		title VARCHAR(255) NOT NULL DEFAULT '',
		pos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		params_json LONGTEXT,
		PRIMARY KEY (id),
		INDEX parent_id (parent_id),
		INDEX trigger_id (trigger_id)
	) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['decision_node'] = 'decision_node';
}

// ===========================================================================
// Rename 'worker_event' to 'notification'

if(isset($tables['worker_event']) && !isset($tables['notification'])) {
	$db->Execute('ALTER TABLE worker_event RENAME notification');
	$db->Execute("DELETE FROM worker_view_model WHERE view_id = 'home_myevents'");
}

// ===========================================================================
// Introduce a context_merge_history table

if(!isset($tables['context_merge_history'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS context_merge_history (
			context VARCHAR(128) DEFAULT '' NOT NULL,
			from_context_id INT UNSIGNED DEFAULT 0 NOT NULL,
			to_context_id INT UNSIGNED DEFAULT 0 NOT NULL,
			updated INT UNSIGNED DEFAULT 0 NOT NULL,
			PRIMARY KEY (context, from_context_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
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

// ===========================================================================
// Add reply_address_id/reply_personal/reply_signature fields to groups

list($columns, $indexes) = $db->metaTable('team');

if(!isset($columns['reply_address_id']))
	$db->Execute("ALTER TABLE team ADD COLUMN reply_address_id INT UNSIGNED DEFAULT 0 NOT NULL");
if(!isset($columns['reply_personal']))
	$db->Execute("ALTER TABLE team ADD COLUMN reply_personal VARCHAR(128) DEFAULT '' NOT NULL");
if(isset($columns['signature']) && !isset($columns['reply_signature']))
	$db->Execute("ALTER TABLE team CHANGE COLUMN signature reply_signature TEXT");
	
if(!isset($column['reply_address_id'])) {
	// Migrate from group settings (and in code)
	$results = $db->GetArray(sprintf("SELECT group_id, setting, value FROM group_setting WHERE setting IN ('reply_from','reply_personal')"));
	foreach($results as $row) {
		if(empty($row['value']))
			continue;
		
		switch($row['setting']) {
			case 'reply_from':
				if(null == ($address = DAO_Address::lookupAddress($row['value'], true)))
					continue;
				
				$db->Execute(sprintf("UPDATE team SET reply_address_id = %d WHERE id = %d",
					$address->id,
					$row['group_id']
				));
				break;
				
			case 'reply_personal':
				$db->Execute(sprintf("UPDATE team SET reply_personal = %s WHERE id = %d",
					$db->qstr($row['value']),
					$row['group_id']
				));
				break;
		}
	}
	
	// Remove redundant settings from DB
	$db->Execute("DELETE FROM group_setting WHERE setting IN ('reply_from','reply_personal')");;
}

$db->Execute("DELETE FROM group_setting WHERE setting IN ('reply_personal_with_worker')");

// ===========================================================================
// Add reply_address_id/reply_personal/reply_signature fields to buckets

list($columns, $indexes) = $db->metaTable('category');

if(!isset($columns['reply_address_id']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_address_id INT UNSIGNED DEFAULT 0 NOT NULL");
if(!isset($columns['reply_personal']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_personal VARCHAR(128) DEFAULT '' NOT NULL");
if(!isset($columns['reply_signature']))
	$db->Execute("ALTER TABLE category ADD COLUMN reply_signature TEXT");

// ===========================================================================
// Add address_outgoing

if(!isset($tables['address_outgoing'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS address_outgoing (
			address_id INT UNSIGNED DEFAULT 0 NOT NULL,
			is_default TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
			reply_personal VARCHAR(128) DEFAULT '' NOT NULL,
			reply_signature TEXT,
			PRIMARY KEY (address_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);
	
	// Migrate the default sender address
	
	$default_reply_from = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_reply_from' AND plugin_id='cerberusweb.core'");
	$default_reply_personal = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_reply_personal' AND plugin_id='cerberusweb.core'");
	$default_reply_signature = $db->GetOne("SELECT value FROM devblocks_setting WHERE setting = 'default_signature' AND plugin_id='cerberusweb.core'");
	
	if(!empty($default_reply_from) && null != ($address = DAO_Address::lookupAddress($default_reply_from, true))) {
		$db->Execute("UPDATE address_outgoing SET is_default = 0");
		$db->Execute(sprintf("INSERT IGNORE INTO address_outgoing (address_id, is_default, reply_personal, reply_signature) ".
			"VALUES (%d, %d, %s, %s)",
			$address->id,
			1,
			$db->qstr($default_reply_personal),
			$db->qstr($default_reply_signature)
		));
	}
	
	$db->Execute("DELETE FROM devblocks_setting WHERE plugin_id = 'cerberusweb.core' AND setting IN ('default_reply_from','default_reply_personal','default_signature','default_signature_pos')");
	
	// Import from group addresses
	$db->Execute("INSERT IGNORE INTO address_outgoing (address_id,is_default) SELECT DISTINCT reply_address_id, 0 FROM team WHERE reply_address_id != 0");
	
}	
	
return TRUE;
