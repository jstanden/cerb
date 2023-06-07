<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Automation Event Listener

if(!isset($tables['automation_event_listener'])) {
	$sql = sprintf("
		CREATE TABLE `automation_event_listener` (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL DEFAULT '',
		`event_name` varchar(255) NOT NULL DEFAULT '',
		`is_disabled` tinyint unsigned NOT NULL DEFAULT 0,
		`priority` tinyint unsigned NOT NULL DEFAULT 50,
		`created_at` int unsigned NOT NULL DEFAULT 0,
		`updated_at` int unsigned NOT NULL DEFAULT 0,
		`event_kata` mediumtext,
		`workflow_id` int unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		INDEX (event_name),
		INDEX (updated_at)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['automation_event_listener'] = 'automation_event_listener';
}

// ===========================================================================
// Drop `automation_event.automations_kata`

list($columns, ) = $db->metaTable('automation_event');

if(array_key_exists('automations_kata', $columns)) {
	// Migrate automation_event KATA to event listeners
	$db->ExecuteMaster("INSERT IGNORE INTO automation_event_listener (name, event_name, priority, created_at, updated_at, event_kata) SELECT 'Default', name, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), automations_kata FROM automation_event");
	
	// Migrate event changeset history to the new 'Default' listener records
	$db->ExecuteMaster("UPDATE IGNORE record_changeset SET record_type = 'automation_event_listener', record_id = (select id from automation_event_listener where name = 'Default' and event_name = (select cast(name as binary) from automation_event where id = record_changeset.record_id)) where record_type = 'automation_event'");
	
	// Drop the old column
	$db->ExecuteMaster('ALTER TABLE automation_event DROP COLUMN automations_kata');
}

// ===========================================================================
// Finish up

return TRUE;
