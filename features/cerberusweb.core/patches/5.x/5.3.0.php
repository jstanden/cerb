<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$tables = $db->metaTables();

// ===========================================================================
// attachment_link 

if(!isset($tables['attachment_link'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS attachment_link (
			guid VARCHAR(64) NOT NULL DEFAULT '',
			attachment_id INT UNSIGNED NOT NULL,
			context VARCHAR(128) DEFAULT '' NOT NULL,
			context_id INT UNSIGNED NOT NULL,
			PRIMARY KEY (attachment_id, context, context_id),
			INDEX guid (guid),
			INDEX attachment_id (attachment_id),
			INDEX context (context),
			INDEX context_id (context_id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);

	$tables['attachment_link'] = 'attachment_link';
}

// ===========================================================================
// attachment

if(!isset($tables['attachment']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('attachment');

// Add updated timestamp
if(!isset($columns['updated'])) {
	$db->Execute("ALTER TABLE attachment ADD COLUMN updated INT UNSIGNED NOT NULL DEFAULT 0, ADD INDEX updated (updated)");
	$db->Execute("UPDATE attachment LEFT JOIN message ON (attachment.message_id=message.id) SET attachment.updated=message.created_date WHERE attachment.updated = 0");
}

// Migrate attachment -> attachment_link (message) 
if(isset($columns['message_id'])) { // ~2.56s
	$sql = "INSERT IGNORE INTO attachment_link (attachment_id, context, context_id, guid) ".
		"SELECT id AS attachment_id, 'cerberusweb.contexts.message' AS context, message_id AS context_id, UUID() as guid ".
		"FROM attachment ";
	$db->Execute($sql);
	
	$db->Execute("ALTER TABLE attachment DROP COLUMN message_id");
}

// ===========================================================================
// view_filters_preset

if(!isset($tables['view_filters_preset']))
	return FALSE;
	
list($columns, $indexes) = $db->metaTable('view_filters_preset');

if(!isset($columns['sort_json'])) {
	$db->Execute("ALTER TABLE view_filters_preset ADD COLUMN sort_json TEXT");
}

return TRUE;
