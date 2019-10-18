<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Change Twitter message content to utf8mb4

if(!isset($tables['twitter_message'])) {
	$logger->error("The 'twitter_message' table does not exist.");
	return FALSE;
}

list($columns,) = $db->metaTable('twitter_message');

if(@$columns['content'] && 0 != strcasecmp($columns['content']['type'], 'blob')) {
	$db->ExecuteMaster("ALTER TABLE twitter_message MODIFY COLUMN content blob");
}

return TRUE;