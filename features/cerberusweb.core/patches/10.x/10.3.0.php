<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Index attachment storage profiles for cron.storage

if(!isset($tables['attachment']))
	return FALSE;

list(,$indexes) = $db->metaTable('attachment');

if(!array_key_exists('profile_updated', $indexes)) {
	$db->ExecuteMaster('ALTER TABLE attachment ADD INDEX `profile_updated` (`storage_extension`,`storage_profile_id`,`updated`)');
}

if(array_key_exists('storage_profile_id', $indexes)) {
	$db->ExecuteMaster('ALTER TABLE attachment DROP INDEX storage_profile_id');
}

// ===========================================================================
// Add `available_at` to `queue_message`

if(!isset($tables['queue_message']))
	return FALSE;

list($columns,) = $db->metaTable('queue_message');

if(!array_key_exists('available_at', $columns)) {
	$db->ExecuteMaster("ALTER TABLE queue_message ADD COLUMN available_at int unsigned not null default 0");
}


// ===========================================================================
// Add `record_changeset` table

if(!isset($tables['record_changeset'])) {
	$sql = sprintf("
		CREATE TABLE `record_changeset` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`created_at` int(10) unsigned NOT NULL DEFAULT 0,
		`record_type` varchar(64) NOT NULL DEFAULT '',
		`record_id` int(10) unsigned NOT NULL DEFAULT 0,
		`worker_id` int(10) unsigned NOT NULL DEFAULT 0,
		`storage_sha1hash` varchar(40) NOT NULL DEFAULT '',
		`storage_size` int(10) unsigned NOT NULL DEFAULT '0',
		`storage_key` varchar(64) NOT NULL DEFAULT '',
		`storage_extension` varchar(128) NOT NULL DEFAULT '',
		`storage_profile_id` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (id),
		INDEX (record_type, record_id),
		INDEX (storage_extension, storage_profile_id, created_at),
		INDEX (created_at),
		INDEX (worker_id)
		) ENGINE=%s
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql) or die("[MySQL Error] " . $db->ErrorMsgMaster());
	
	$tables['record_changeset'] = 'record_changeset';
}

// ===========================================================================
// Finish up

return TRUE;