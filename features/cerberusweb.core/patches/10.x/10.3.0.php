<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

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