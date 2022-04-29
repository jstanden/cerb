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
// Finish up

return TRUE;